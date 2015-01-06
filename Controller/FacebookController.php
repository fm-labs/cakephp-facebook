<?php
App::uses('FacebookAppController','Facebook.Controller');

/**
 * @property FacebookComponent $Facebook
 * @property AuthComponent $Auth
 */
class FacebookController extends FacebookAppController {

	public $components = array('Session');

	public $uses = array();

/**
 * @see Controller::beforeFilter()
 */
	public function beforeFilter() {
		parent::beforeFilter();

		if (!$this->Components->loaded('Facebook')) {
			$this->Components->load('Facebook.Facebook');
		}

		//@TODO Move to FacebookComponent
		if ($this->Components->enabled('Auth')) {
			if ($this->Facebook->useAuth) {
				$this->Auth->allow('connect', 'login');
			} else {
				$this->Auth->allow('connect', 'disconnect');
			}
		}
	}

/**
 * Get redirect url
 *
 * @return mixed|string
 * @TODO Move to FacebookComponent
 */
	protected function _getRedirectUrl() {
		$redirectUrl = '/';

		if ($this->Components->enabled('Auth')) {
			$redirectUrl = $this->Auth->redirectUrl();
		}

		if ($this->Session->check('Facebook.redirect')) {
			$redirectUrl = $this->Session->read('Facebook.redirect');
			$this->Session->delete('Facebook.redirect');
		}

		if (Router::normalize($redirectUrl) == Router::normalize(array('action' => 'connect'))) {
			$redirectUrl = '/';
		}

		return $redirectUrl;
	}

/**
 * Set redirect url
 *
 * @param $redirectUrl
 * @TODO Move to FacebookComponent
 */
	protected function _setRedirectUrl($redirectUrl) {
		$this->Session->write('Facebook.redirect', $redirectUrl);
	}

/**
 * Connect with facebook
 *
 * 1. Redirect the user to the facebook login dialog.
 * 2. Facebook redirects the user back here.
 * 3. Redirect user to the initial referer url (if available)
 *
 * @TODO Move to FacebookComponent
 * @TODO Support for 'scope' and 'next' query params
 */
	public function connect() {
		if ($this->Facebook->getSession()) {
			// Resume facebook session
			$this->Facebook->reloadUser();
			$this->Facebook->flash("You are already connected with Facebook", $this->_getRedirectUrl());

		} elseif ($this->Facebook->connect()) {
			// Created facebook session
			if ($this->Facebook->useAuth) {
				$this->login();
				return;
			}
			$this->Facebook->flash("Connected with Facebook", $this->_getRedirectUrl());

		} else {
			// No active facebook session
			$this->_setRedirectUrl($this->referer());
			$this->Facebook->flash('Connect with facebook', $this->Facebook->getLoginUrl());
		}
	}

/**
 * Login with Facebook
 * Requires AuthComponent with 'authenticate' set to 'Facebook.Facebook'
 *
 * @TODO Move to FacebookComponent
 * @TODO Support for 'scope' and 'next' query params
 */
	public function login() {
		if (!$this->Components->enabled('Auth') || !$this->Facebook->useAuth) {
			if (Configure::read('debug') > 0) {
				throw new CakeException(__('Authentication is not enabled'));
			}
			throw new NotFoundException();
		}

		if ($this->Auth->user()) {
			$this->Facebook->flash('Already logged in', $this->_getRedirectUrl());

		} elseif ($this->Auth->login()) {
			$this->Facebook->flash('Login successful', $this->_getRedirectUrl());

		} else {
			$this->_setRedirectUrl($this->referer());
			$this->Facebook->flash('Login with facebook', $this->Facebook->getLoginUrl());
		}
	}

/**
 * Disconnect user from application
 * without logging the user out of facebook itself
 *
 * @TODO Move to FacebookComponent
 */
	public function disconnect() {
		$this->_setRedirectUrl($this->referer());

		$this->Facebook->disconnect();
		$this->Facebook->flash(__('Disconnected from Facebook'), $this->_getRedirectUrl());
	}

/**
 * Logout from facebook
 *
 * Disconnect
 * Redirect client to facebook logout page
 * Facebook redirects back to the initial referrer
 *
 * @return void
 * @TODO Custom redirect URL
 * @TODO Move to FacebookComponent
 */
	public function logout() {
		$this->Facebook->getSession();
		if ($this->Facebook->user()) {
			$this->_setRedirectUrl($this->referer());
			$logoutUrl = $this->Facebook->getLogoutUrl(array('action' => 'logout_success'));
			$this->Facebook->flash(__('Logout'), $logoutUrl);
		} else {
			$this->Facebook->flash(__('Already logged out'), $this->_getRedirectUrl());
		}
	}

	public function logout_success() {
		if ($this->Facebook->useAuth) {
			$this->Auth->logout();
		}
		$this->Facebook->disconnect();
		$this->Facebook->flash(__('You have been logged out'), $this->_getRedirectUrl());
	}

/**
 * Request permission
 *
 * @param string $perms Permission name. Comma-separated list for multiple permissions.
 */
	public function permission_request($perms = null) {
		$this->Facebook->requestUserPermission($perms, $this->referer());
	}

/**
 * Revoke permission
 *
 * @param string $perm
 */
	public function permission_revoke($perm = null) {
		if ($this->Facebook->revokeUserPermission($perm)) {
			$this->Session->setFlash(__('Permission %s has been revoked', $perm));
		} else {
			$this->Session->setFlash(__('Permission %s could not be revoked. Please try again.', $perm));
		}
		$this->redirect($this->referer());
	}

}
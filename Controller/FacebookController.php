<?php
App::uses('FacebookAppController','Facebook.Controller');

/**
 * @property FacebookComponent $Facebook
 * @property AuthComponent $Auth
 */
class FacebookController extends FacebookAppController {

	public $components = array('Session', 'Facebook.Facebook');

	public $uses = array();

/**
 * @see Controller::beforeFilter()
 * @throws CakeException
 */
	public function beforeFilter() {
		parent::beforeFilter();

		if ($this->Components->enabled('Auth')) {
			$this->Auth->allow('index', 'connect', 'disconnect', 'login', 'token');
		}
	}

	protected function _getRedirectUrl() {
		//if ($this->Components->enabled('Auth')) {
		//    return $this->Auth->redirectUrl($this->Session->read('Facebook.redirect'));
		//}
		return ($this->Session->read('Facebook.redirect')) ?: '/';
	}

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
 */
	public function connect() {
		// Resume active session
		if ($this->Facebook->getSession()) {
			$this->Facebook->reloadUser();
			$this->Facebook->flash("You are already connected with Facebook", $this->getRedirectUrl());

			// Load session from redirect
		} elseif ($this->Facebook->connect()) {
			//@TODO
			if ($this->Components->enabled('Auth')) {
				$this->login();
				return;
			}
			$this->Facebook->flash("Connected with Facebook", $this->getRedirectUrl());

			// No session
		} else {
			//$referrer = $this->referer();
			//$this->Session->write('Facebook.redirect', $referrer);

			$loginUrl = $this->Facebook->getLoginUrl();
			$this->Facebook->flash('Connect with facebook', $loginUrl);
		}
	}

/**
 * Login with Facebook
 * Requires AuthComponent with 'authenticate' set to 'Facebook.Facebook'
 *
 * @TODO Move to FacebookComponent
 */
	public function login() {
		/*
		if (!$this->Components->enabled('Auth')) {
			if (Configure::read('debug') > 0) {
				throw new CakeException(__('Authentication is not enabled'));
			}
			throw new NotFoundException();
		}
		*/

		if ($this->Auth->user()) {
			$this->Facebook->flash('Already logged in', $this->getRedirectUrl());

		} elseif ($this->Auth->login()) {
			$this->Facebook->flash('Login successful', $this->getRedirectUrl());
		} else {
			$this->setRedirectUrl($this->referer());

			//$loginSuccessUrl = array('action' => 'login_success', '?' => array('redirect_url' => $this->Auth->redirectUrl()));
			$loginUrl = $this->Facebook->getLoginUrl();
			$this->Facebook->flash('Login with facebook', $loginUrl);
		}
	}

/**
 * Disconnect user from application
 * without logging the user out of facebook itself
 *
 * @TODO Move to FacebookComponent
 */
	public function disconnect() {
		$this->Facebook->disconnect();
		$this->Session->setFlash(__('Disconnected from Facebook'));
		$this->redirect(array('action' => 'connect'));
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
		$next = $this->referer();

		$this->Facebook->disconnect();
		$logoutUrl = $this->Facebook->getLogoutUrl($next);
		$this->redirect($logoutUrl);
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
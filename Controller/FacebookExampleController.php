<?php
App::uses('FacebookAppController', 'Facebook.Controller');

/**
 * @property FacebookComponent $Facebook
 * @property AuthComponent $Auth
 */
class FacebookExampleController extends FacebookAppController {

	public $components = array('Session', 'Facebook.Facebook');

	public $uses = array();

/**
 * @see Controller::beforeFilter()
 * @throws NotFoundException
 */
	public function beforeFilter() {
		parent::beforeFilter();

		// do not show in production
		if (Configure::read('debug') > 0) {
			throw new NotFoundException();
		}

		if ($this->Components->enabled('Auth')) {
			if ($this->Facebook->useAuth) {
				$this->Auth->allow('connect');
			} else {
				$this->Auth->allow('connect', 'disconnect');
			}
		}
	}

/**
 * Connect method
 */
	public function connect() {
		if ($this->Facebook->login()) {
			// Attempt to start / resume a facebook session.
			// Requires 'useAuth' enabled, the AuthComponent loaded,
			// and the FacebookAuthenticate adapter in place
			$this->Session->setFlash(__('You logged in with facebook'));
			$this->redirect($this->Facebook->getRedirectUrl());

		} elseif ($this->Facebook->connect()) {
			// Attempt to start / resume facebook session,
			// if login fails or 'useAuth' is disabled
			$this->Session->setFlash(__('You are connected with facebook'));
			$this->redirect($this->Facebook->getRedirectUrl());

		} else {
			// If no facebook session is found,
			// redirect to facebook login oauth dialog
			debug($this->referer());
			$this->Facebook->flash('CONNECT', $this->Facebook->getLoginUrl());
		}
	}

/**
 * Disconnect method
 */
	public function disconnect() {
		if ($this->Facebook->disconnect()) {
			$this->Session->setFlash(__('You are disconnected from facebook'));
		} else {
			$this->Session->setFlash(__('Failed to disconnected from facebook'));
		}
		$this->redirect($this->referer());
	}

/**
 * Logout method
 *
 * Redirect to Facebook logout page
 */
	public function logout() {
		$this->Facebook->logout($this->referer());
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
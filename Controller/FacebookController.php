<?php
App::uses('FacebookAppController','Facebook.Controller');

/**
 * @property FacebookUser $FacebookUser
 * @property FacebookComponent $Facebook
 */
class FacebookController extends FacebookAppController {
	
	public $components = array('Session');

    public $uses = array();

	public function beforeFilter() {
		parent::beforeFilter();

        if (!$this->Components->loaded('Facebook')) {
            throw new CakeException(__('FacebookComponent is not loaded'));
        }

		if ($this->Auth) {
			$this->Auth->allow('connect','disconnect','login');
		}
        $this->response->disableCache();
	}

	public function connect() {

		//$this->Facebook->connect();
        //debug($this->Facebook);
		if (!$this->Facebook->user()) {
			$loginUrl = $this->Facebook->getLoginUrl();
			$this->Facebook->flash('Connect with facebook', $loginUrl);
		} else {
            $this->Session->write('FacebookUser', $this->Facebook->user());
            $this->Session->setFlash(__('Connected via Facebook as %s',$this->Facebook->user('name')));
			$this->redirect('/');
		}
	}

    public function disconnect() {
        $this->Facebook->disconnect(true);
        $this->Session->delete('FacebookUser');
        $this->Session->setFlash(__('Disconnected from Facebook'));
        $this->redirect($this->referer());
    }

	/**
	 * Login with Facebook
	 * Requires AuthComponent with 'authenticate' set to 'Facebook.Facebook'
	 * If FacebookComponent::autoConnect is disabled, requires calling FacebookComponent::connect()
	 * or FacebookConnect::connect() before calling AuthComponent::login()
	 *
     * @todo Refactor me
	 */
	public function login() {
		
		$this->Facebook->connect();
		if ($this->Auth) {
			// if (!$this->Auth->user()) {
				if ($this->Auth->login()) {
					$this->Session->setFlash(__('Login successful'));
					//$this->redirect($this->Auth->redirectUrl());
				} else {
					$loginUrl = $this->Facebook->getLoginUrl();
					$this->flash('Login with facebook', $loginUrl);
				}
			// } else {
			//	$this->Session->setFlash(__('Already logged in'));
			// }
		} else {
			//@todo handle AuthComponent-not-loaded
		}
	}

	/**
	 * Logout from app and facebook
     *
     * @todo Refactor me
	 */
	public function logout() {
		$redirect = $this->Auth->logout();
		$this->Session->setFlash(__('Logged out'));
		$this->redirect($redirect);
	}
	
    /**
     * Request permission
     *
     * @param string $perms Permission name. Comma-separated list for multiple permissions.
     */
    public function permission_request($perms = null) {
        $this->Facebook->requestPermission($perms, $this->referer());
    }

    /**
     * Revoke permission
     *
     * @param string $perm
     */
    public function permission_revoke($perm = null) {

        if ($this->Facebook->revokePermission($perm)) {
            $this->Session->setFlash(__('Permission %s has been revoked', $perm));
        } else {
            $this->Session->setFlash(__('Permission %s could not be revoked. Please try again.', $perm));
        }
        $this->redirect($this->referer());
    }
	
}
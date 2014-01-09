<?php
App::uses('FacebookAppController','Facebook.Controller');

/**
 * @property FacebookUser $FacebookUser
 * @property FacebookComponent $Facebook
 * @property AuthComponent $Auth
 */
class FacebookController extends FacebookAppController {
	
	public $components = array('Session','Facebook.Facebook');

    public $uses = array();

	public function beforeFilter() {
		parent::beforeFilter();

        if (!$this->Components->enabled('Facebook')) {
            throw new CakeException(__('FacebookComponent is not enabled'));
        }

		if ($this->Components->enabled('Auth')) {
			$this->Auth->allow('connect','disconnect','login');
		}
	}

    /**
     * Connect with facebook
     *
     * 1. Redirect the user to the facebook login dialog.
     * 2. Facebook redirects the user back here.
     * 3. Redirect user to the initial referer url (if available)
     */
    public function connect() {

        $this->Facebook->connect();
        $referer = Router::url($this->referer('/', true),true);
		if (!$this->Facebook->user()) {
            $redirectUrl = Router::url(array(
                'action'=>'connect',
                '?' => array('goto' => $referer)
            ),true);
			$loginUrl = $this->Facebook->getLoginUrl($redirectUrl);
			$this->Facebook->flash('Connect with facebook', $loginUrl);
		} else {
            $goto = ($this->request->query('goto'))
                ? $this->request->query('goto')
                : $referer;
            $this->Session->setFlash(__('Connected via Facebook as %s',$this->Facebook->user('name')));
			$this->redirect($goto);
		}
	}

    /**
     * Disconnect user from application
     * without logging the user out of facebook itself
     */
    public function disconnect() {

        $this->Facebook->disconnect(true);
        $this->Session->setFlash(__('Disconnected from Facebook'));
        $this->redirect($this->referer());
    }

	/**
	 * Login with Facebook
	 * Requires AuthComponent with 'authenticate' set to 'Facebook.Facebook'
	 */
	public function login() {
		
		$this->Facebook->connect();
		if (!$this->Components->enabled('Auth')) {
            if (Configure::read('debug') > 1) {
                throw new CakeException(__('AuthComponent is not enabled'));
            }
            throw new NotFoundException();
        }

        if (!$this->Auth->user()) {
            if ($this->Auth->login()) {
                $this->Session->setFlash(__('Login successful'));
            } else {
                $loginUrl = $this->Facebook->getLoginUrl();
                $this->Facebook->flash('Login with facebook', $loginUrl);
                return;
            }
        }
        $this->redirect($this->Auth->redirectUrl());
	}

	/**
	 * Logout from facebook
     * Redirects to facebook logout page
     *
     * @return void
	 */
	public function logout() {

        $referer = $this->referer();
        $redirectUrl = Router::url(array(
            'action' => 'logout_success',
            '?' => array('goto' => $referer)
        ),true);
        $logoutUrl = $this->Facebook->getLogoutUrl($redirectUrl);
        $this->redirect($logoutUrl);
	}

    /**
     * Logout from facebook was successful
     *
     * The user has successfully logged out of facebook,
     * now logout of application and destroy the facebook session.
     * Redirects to AuthComponent::logoutRedirect.
     * Redirect url can be overridden by using the
     * 'goto' query param
     *
     * @return void
     */
    public function logout_success() {

        $goto = ($this->request->query('goto'))
            ? $this->request->query('goto')
            : null;

        if ($this->Components->enabled('Auth')) {
            $this->Auth->logoutRedirect = $goto;
            $redirectUrl = $this->Auth->logout();
        } else {
            $this->Facebook->disconnect(true);
            $redirectUrl = ($goto) ? $goto : '/';
        }

        $this->Session->setFlash(__('Logged out'));
        $this->redirect($redirectUrl);
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
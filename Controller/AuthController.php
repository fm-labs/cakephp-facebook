<?php
App::uses('FacebookAppController','Facebook.Controller');

/**
 * @property FacebookUser $FacebookUser
 * @property FacebookComponent $Facebook
 * @property AuthComponent $Auth
 */
class AuthController extends FacebookAppController {

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

/**
 * Connect with facebook
 *
 * 1. Redirect the user to the facebook login dialog.
 * 2. Facebook redirects the user back here.
 * 3. Redirect user to the initial referer url (if available)
 */
	public function connect() {
		//$referrer = Router::url($this->referer('/', true), true);

        if ($this->Facebook->getSession()) {
            $this->Session->setFlash("You are already connected with Facebook");
            $this->Facebook->reloadUser();
        } elseif ($this->Facebook->connect()) {
            $this->Session->setFlash("Connected with Facebook");
        } else {
            $loginUrl = $this->Facebook->getLoginUrl(null, array('email'));
            //$this->Facebook->flash('Connect with facebook', $loginUrl);
            $this->set('loginUrl', $loginUrl);
        }
	}

/**
 * Disconnect user from application
 * without logging the user out of facebook itself
 */
	public function disconnect() {
		$this->Facebook->disconnect();
		$this->Session->setFlash(__('Disconnected from Facebook'));
		$this->redirect(array('action' => 'connect'));
	}

/**
 * Login with Facebook
 * Requires AuthComponent with 'authenticate' set to 'Facebook.Facebook'
 *
 * @todo proper exception handling
 */
	public function login() {
		if (!$this->Components->enabled('Auth')) {
			if (Configure::read('debug') > 1) {
				throw new CakeException(__('AuthComponent is not enabled in your AppController'));
			}
			throw new NotFoundException();
		}

		//if (!$this->Auth->user()) {
			if ($this->Auth->login()) {
				$this->Session->setFlash(__('Login successful'));
                //$this->redirect($this->Auth->redirectUrl());
			} else {
                //$loginSuccessUrl = array('action' => 'login_success', '?' => array('redirect_url' => $this->Auth->redirectUrl()));
				$loginUrl = $this->Facebook->getLoginUrl();
				//$this->Facebook->flash('Login with facebook', $loginUrl);
                $this->set('loginUrl', $loginUrl);
				return;
			}
		//}
	}


    /*
    public function login_success() {
        $redirectUrl = urldecode($this->request->query('redirect_url'));
    }
    */

/**
 * Logout from facebook
 * Redirects to facebook logout page
 *
 * @return void
 * @TODO Refactor with configurable redirect URL
 */
	public function logout() {
		$next = $this->referer();
		$redirectUrl = Router::url(array(
			'action' => 'logout_success',
			'?' => array('next' => $next)
		), true);
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
 * @TODO Refactor
 */
	public function logout_success() {
		$next = ($this->request->query('next')) ?: '/';

		if ($this->Components->enabled('Auth')) {
			$this->Auth->logoutRedirect = $next;
            $next = $this->Auth->logout();
		}

        $this->Facebook->disconnect();
		$this->Session->setFlash(__('Logged out'));
		$this->redirect($next);
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


/**
 * Debug access token
 */
    public function token() {

        if ($this->request->is('post') && isset($this->request->data['accessToken'])) {
            $this->Session->write('Facebook.Auth.accessToken', $this->request->data['accessToken']);
            $this->Session->setFlash('Updated Facebook Auth Token to: ' . $this->request->data['accessToken']);
            $this->redirect(array('action' => 'token'));
        } else {
            $this->request->data = $this->Session->read('Facebook.Auth');
        }
    }
}
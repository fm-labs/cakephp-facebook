<?php
App::uses('Component', 'Controller/Component');
App::uses('FacebookApi', 'Facebook.Lib');

use Facebook\FacebookRequest;

/**
 * Facebook Component
 *
 * @property SessionComponent $Session
 */
class FacebookComponent extends Component {

	public $components = array('Session');

/**
 * @var Controller
 */
	public $Controller;

/**
 * @var FacebookApi
 */
	public $FacebookApi;

/**
 * @var bool
 */
	public $useAuth = false;

/**
 * @var bool
 */
	public $useFlash = false;

/**
 * @see Component::initialize()
 */
	public function initialize(Controller $controller) {
		$this->Controller = $controller;
		$this->FacebookApi = FacebookApi::getInstance();

		// override settings from config
		$this->_set($this->FacebookApi->getConfig());
	}

/**
 * @see Component::startup()
 */
	public function startup(Controller $controller) {
		$this->FacebookApi->getSession();
	}

/**
 * Provide access to the FacebookApi methods
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->FacebookApi, $method), $params);
	}

/**
 * @see FacebookApi::getSession()
 */
	public function getSession() {
		return $this->FacebookApi->getSession();
	}

/**
 * @see FacebookApi::getLoginUrl()
 */
	public function getLoginUrl($scope = array()) {
		return $this->FacebookApi->getLoginUrl(null, $scope);
	}

/**
 * @see FacebookApi::getLogoutUrl()
 */
	public function getLogoutUrl($redirectUrl = null) {
		return $this->FacebookApi->getLogoutUrl($redirectUrl);
	}

/**
 * @see FacebookApi::getUser()
 */
	public function getUser($key = null) {
		return $this->FacebookApi->getUser($key);
	}

/**
 * Attempt to create a facebook session from facebook's connect redirect
 */
	public function connect() {
		if ($this->FacebookApi->getSession() || $this->FacebookApi->handleConnectRedirect()) {
			$this->FacebookApi->reloadUser();
			return true;
		}

		return false;
	}

/**
 * @see FacebookApi::disconnect()
 */
	public function disconnect() {
		$this->FacebookApi->disconnect();
		return true;
	}

/**
 * Attempt to authenticate user from facebook session
 *
 * @return bool
 * @throws CakeException
 */
	public function login() {
		// start facebook session
		if (!$this->connect()) {
			return false;
		}

		// check preconditions
		if (!$this->useAuth) {
			return true;
		}

		if (!$this->Controller->Components->loaded('Auth')) {
			throw new CakeException("Auth component not loaded");
		}

		//@TODO check if FacebookAuthenticate is attached to AuthComponent

		//@TODO check if already authenticated

		// authenticate
		if ($this->Controller->Auth->login()) {
			return true;
		}

		return false;
	}

/**
 * Logout
 *
 * Log out from app and Facebook
 */
	public function logout($next = null) {
		if ($this->useAuth) {
			$next = ($next) ?: $this->Controller->Auth->logoutRedirect;
			// facebook logout url has to be built before triggering Auth::logout()
			// as the facebook session will be disconnected on logout
			$logoutUrl = $this->FacebookApi->getLogoutUrl($next);
			$this->Controller->Auth->logout();
		} else {
			$logoutUrl = $this->FacebookApi->getLogoutUrl($next);
		}
		$this->flash('Logout', $logoutUrl);
	}

/**
 * @see FacebookApi::reloadUser()
 */
	public function reloadUser() {
		$this->FacebookApi->reloadUser();
	}

/**
 * Convenience wrapper for getUser()
 */
	public function user($key = null) {
		return $this->getUser($key);
	}

/**
 * @see FacebookApi::getUserPermissions()
 */
	public function getUserPermissions() {
		return $this->FacebookApi->getUserPermissions();
	}

/**
 * @see FacebookApi::checkUserPermission()
 */
	public function checkUserPermission($perm) {
		return $this->FacebookApi->checkUserPermission($perm);
	}

/**
 * Request Permission(s) via OAuth Dialog
 *
 * Redirect to Facebook login page, where user has to grant permission
 *
 * @param string|array $perm Comma-separated string or array list of permissions
 * @param string|null $next Internal redirect url after returning back from facebook
 */
	public function requestUserPermission($perm, $next = null) {
		$this->setRedirectUrl($next);
		$loginUrl = $this->FacebookApi->getUserPermissionRequestUrl($perm);
		$this->flash('Requesting Facebook permission', $loginUrl);
	}

/**
 * Revoke Permission via Graph API
 *
 * @see FacebookApi::deleteUserPermission()
 * @param string $perm Permission name
 * @return bool
 */
	public function revokeUserPermission($perm) {
		return $this->FacebookApi->revokeUserPermission($perm);
	}

/**
 * @see Component::beforeRender()
 */
	public function beforeRender(Controller $controller) {
		$controller->helpers['Facebook.Facebook'] = array();
	}

/**
 * @see Controller::flash()
 */
	public function flash($msg, $url, $pause = 2, $layout = 'Facebook.flash') {
		if (!$this->useFlash) {
			$this->redirect($url);
			return;
		}
		$this->Controller->flash($msg, $url, $pause, $layout);
	}

/**
 * @see Controller::redirect()
 */
	public function redirect($url) {
		$this->Controller->redirect($url);
	}

/**
 * Get redirect url
 *
 * @return mixed|string
 */
	public function getRedirectUrl() {
		$redirectUrl = '/';
		if ($this->Session->check('Facebook.redirect')) {
			$redirectUrl = $this->Session->read('Facebook.redirect');
			$this->Session->delete('Facebook.redirect');
		}

		return $redirectUrl;
	}

/**
 * Set redirect url
 *
 * @param $redirectUrl
 */
	public function setRedirectUrl($redirectUrl = null) {
		if ($redirectUrl === null) {
			$redirectUrl = $this->Controller->request->here;
		}
		$this->Session->write('Facebook.redirect', $redirectUrl);
	}

}
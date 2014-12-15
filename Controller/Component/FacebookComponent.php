<?php
App::uses('Component', 'Controller/Component');
App::uses('FacebookConnect', 'Facebook.Lib');

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
 * Automatically connect user on initialize.
 * If set to FALSE, requires calling connect() manually (e.g. from the controller)
 * Default: TRUE
 *
 * @var bool
 */
	public $autoConnect = false;

/**
 * Automatically load FacebookHelper before rendering
 * Default: TRUE
 *
 * @var bool
 */
	public $useHelper = true;

/**
 * Auto enables Facebook Authentication (if AuthComponent is loaded)
 * Default: TRUE
 *
 * @var bool
 */
	public $useAuth = true;

/**
 * Use flash messages for redirects
 * Default: TRUE
 *
 * @var bool
 */
	public $useFlash = true;

/**
 * @var FacebookConnect
 */
	public $FacebookConnect;

/**
 * @see Component::initialize()
 * @param Controller $controller
 */
	public function initialize(Controller $controller) {
		$this->Controller =& $controller;
		$this->FacebookConnect = FacebookConnect::getInstance();

		// auto connect
		if ($this->autoConnect) {
			$this->connect();
		}

		// auto load Facebook Authentication
		/*
		if ($this->useAuth
				&& isset($this->Controller->Auth)
				&& !array_key_exists('Facebook.Facebook',$this->Controller->Auth->authenticate)
				&& !in_array('Facebook.Facebook',$this->Controller->Auth->authenticate))
		{
			$this->Controller->Auth->authenticate[] = 'Facebook.Facebook';
		}
		*/
	}

/**
 * @see Component::startup()
 * @param Controller $controller
 */
	public function startup(Controller $controller) {
		// if user is not authenticated but logged in into facebook
		// try to authenticate/sync
		/*
		if ($this->useAuth
				&& !CakeSession::read('FacebookConnect.authLogout')
				&& !$controller->Auth->user()
				&& $this->FacebookConnect->getUser())
		{
			$controller->Auth->login($this->FacebookConnect->getUser());
		}
		*/
	}

/**
 * Connect with Facebook
 *
 * @return void
 */
	public function connect() {
		try {
			$this->FacebookConnect->connect();
		} catch (Exception $e) {
			debug($e);
			throw $e;
		}
	}

/**
 * Disconnect application from Facebook,
 * but do not log the user out of Facebook itself
 *
 * @param bool $destroyFacebookSession
 * @return void
 */
	public function disconnect($destroyFacebookSession = true) {
		$this->FacebookConnect->disconnect($destroyFacebookSession);
	}

	public function login() {
		//@todo implement me
	}

/**
 * Logout user from application and Facebook
 *
 * @param string|array $redirectUrl
 */
	public function logout($redirectUrl = null) {
		if (!$redirectUrl) {
			$redirectUrl = $this->Controller->referer();
		}

		$logoutUrl = $this->getLogoutUrl($redirectUrl);
		$this->flash(__('Disconnecting from facebook'), $logoutUrl);
	}

/**
 * Return facebook user data
 *
 * @param null|string $key
 * @return mixed, $pluginRoute = false
 */
	public function user($key = null) {
		return $this->FacebookConnect->getUser($key);
	}

/**
 * Refresh FacebookConnect session
 *
 * @param array|string $redirectUrl
 */
	public function refresh($redirectUrl = null) {
		$this->FacebookConnect->refresh();
		if ($redirectUrl) {
			// @todo: $this->Controller->redirect($redirectUrl);
		}
	}

/**
 * @see FacebookConnect::getLoginUrl()
 */
	public function getLoginUrl($redirectUrl = null, $scope = array()) {
		return $this->FacebookConnect->getLoginUrl($redirectUrl, $scope);
	}

/**
 * @see FacebookConnect::getLogoutUrl()
 */
	public function getLogoutUrl($redirectUrl = null) {
		return $this->FacebookConnect->getLogoutUrl($redirectUrl);
	}

/**
 * @see Controller::flash()
 * @param $msg
 * @param $url
 * @param int $pause
 * @param string $layout
 * @return void
 */
	public function flash($msg, $url, $pause = 2, $layout = 'flash') {
		if (!$this->useFlash) {
			$this->Controller->redirect($url);
			return;
		}
		$this->Controller->flash($msg, $url, $pause, $layout);
	}

/*****************************************
 *** PERMISSIONS
 *****************************************/

/**
 * @see FacebookConnect::getPermissions()
 */
	public function getPermissions() {
		return $this->FacebookConnect->getPermissions();
	}

/**
 * @see FacebookConnect::checkPermission()
 */
	public function checkPermission($perms) {
		return $this->FacebookConnect->checkPermission($perms);
	}

/**
 * Request Permission(s)
 * Redirect to Facebook login page, where user has to grant permission
 *
 * @param string|array $perms Comma-separated string or array list of permissions
 * @param null|string $redirectUrl
 */
	public function requestPermission($perms, $redirectUrl = null) {
		$loginUrl = $this->getLoginUrl($redirectUrl, $perms);
		$this->flash('Requesting Facebook permission', $loginUrl);
	}

/**
 * Revoke Permission
 *
 * @see FacebookConnect::deletePermission()
 * @param string $perm Permission name
 * @return bool
 */
	public function revokePermission($perm) {
		return $this->FacebookConnect->deletePermission($perm);
	}

/**
 * @see Component::beforeRender()
 */
	public function beforeRender(Controller $controller) {
		// auto load FacebookHelper
		if ($this->useHelper) {
			$controller->helpers['Facebook.Facebook'] = array();
		}
	}

}
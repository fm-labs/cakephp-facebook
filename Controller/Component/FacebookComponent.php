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
 * @see FacebookApi::connect()
 */
	public function connect() {
		return $this->FacebookApi->connect();
	}

/**
 * @see FacebookApi::disconnect()
 */
	public function disconnect() {
		$this->FacebookApi->disconnect();
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
 * Request Permission(s)
 *
 * Redirect to Facebook login page, where user has to grant permission
 *
 * @param string|array $perm Comma-separated string or array list of permissions
 */
	public function requestUserPermission($perm) {
		$loginUrl = $this->FacebookApi->getUserPermissionRequestUrl($perm);
		$this->flash('Requesting Facebook permission', $loginUrl);
	}

/**
 * Revoke Permission
 *
 * @see FacebookApi::deleteUserPermission()
 * @param string $perm Permission name
 * @return bool
 */
	public function revokeUserPermission($perm) {
		return $this->FacebookApi->revokeUserPermission($perm);
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
 * @see Component::beforeRender()
 */
	public function beforeRender(Controller $controller) {
		$controller->helpers['Facebook.Facebook'] = array();
	}

}
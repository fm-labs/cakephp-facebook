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
 * Component settings
 *
 * @var array
 */
    public $settings = array(
        'useFlash' => true
    );

/**
 * @see Component::initialize()
 */
	public function initialize(Controller $controller) {
        $this->Controller = $controller;
		$this->FacebookApi = FacebookApi::getInstance();
	}

/**
 * @see Component::startup()
 */
	public function startup(Controller $controller) {
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
	public function getLoginUrl($next = null, $scope = array()) {
		return $this->FacebookApi->getLoginUrl($next, $scope);
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
 * Convenience wrapper for getUser()
 */
    public function user($key = null) {
        return $this->getUser($key);
    }


    /*****************************************
 *** PERMISSIONS
 *****************************************/

/**
 * @see FacebookApi::getUserPermissions()
 */
	public function getUserPermissions() {
		return $this->FacebookApi->getUserPermissions();
	}

/**
 * @see FacebookApi::checkUserPermission()
 */
	public function checkUserPermission($perms) {
		return $this->FacebookApi->checkUserPermission($perms);
	}

/**
 * Request Permission(s)
 * Redirect to Facebook login page, where user has to grant permission
 *
 * @param string|array $perms Comma-separated string or array list of permissions
 * @param null|string $next
 */
	public function requestUserPermission($perms, $next = null) {
		$loginUrl = $this->getLoginUrl($next, $perms);
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
		return $this->FacebookApi->deleteUserPermission($perm);
	}

/**
 * @see Controller::flash()
 */
    public function flash($msg, $url, $pause = 2, $layout = 'Facebook.flash') {
        if (!$this->settings['useFlash']) {
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
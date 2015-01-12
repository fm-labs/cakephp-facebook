<?php

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;

App::uses('CakeSession', 'Model/Datasource');

/**
 * Class FacebookApi
 */
class FacebookApi {

/**
 * CakePHP Facebook API version
 */
	const VERSION = '2.0';

/**
 * Facebook Graph API version
 */
	const GRAPH_API_VERSION = FacebookRequest::GRAPH_API_VERSION;

/**
 * @var array
 */
	public static $logScopes = array('facebook');

/**
 * @var array
 */
	public $config = array(
		// Facebook App ID
		'appId' => null,
		// Facebook App Secret
		'appSecret' => null,
		// Connect URL
		'connectUrl' => '/facebook/connect/',
		// Connect Redirect Url
		'connectRedirectUrl' => '/',
		// Default Login permissions
		'defaultPermissions' => array(),
		// Enable Authentication
		'useAuth' => false,
		// Enable Flash
		'useFlash' => false,
		// Enable Logging
		'log' => true,
		// Enable Debugging
		'debug' => false,
	);

/**
 * @var Facebook\FacebookSession
 */
	public $FacebookSession;

/**
 * @var Facebook\FacebookRedirectLoginHelper
 */
	public $FacebookRedirectLoginHelper;

/**
 * @var array
 */
	protected $_user;

/**
 * @var array
 */
	protected $_userPermissions;

/**
 * Constructor
 *
 * @throws InvalidArgumentException
 */
	public function __construct() {
		$this->config = array_merge($this->config, Configure::read('Facebook'));

		if (!$this->config['appId'] || !$this->config['appSecret']) {
			throw new InvalidArgumentException('Facebook AppID or AppSecret missing');
		}

		FacebookSession::setDefaultApplication(
			$this->config['appId'],
			$this->config['appSecret']
		);

		CakeSession::start();
	}

/**
 * Connect Facebook user - Handle Connect Redirect from Facebook
 *
 * Attempts to load session from client redirect.
 * This method should be called when the Facebook OAuth Client Login Flow
 * redirects back to app. On success, the access token will be persisted for
 * consecutive calls.
 *
 * @TODO (Auto-)Exchange short-lived token for an extended token
 */
	public function connect() {
		try {
			// handle connect redirect
			if ($this->_loadSessionFromRedirect()) {

				// persist access token
				$this->_updateAccessToken();

				// update user info and permissions
				$this->_loadUser(true);
				$this->_loadUserPermissions(true);

				return true;
			}
		} catch (Exception $ex) {
			$this->log("CONNECT ERROR: " . $ex->getMessage());
		}
		return false;
	}

/**
 * Disconnect Facebook user from app and cleanup persistent data
 */
	public function disconnect() {
		$this->_user = null;
		$this->_userPermissions = null;
		$this->FacebookSession = null;
		$this->FacebookRedirectLoginHelper = null;

		CakeSession::delete('Facebook.Auth');
		CakeSession::delete('Facebook.User');
		CakeSession::delete('Facebook.UserPermissions');
		CakeSession::delete('Facebook');
		CakeSession::delete('FBRLH_state');
	}

/**
 * Load FacebookSession from persisted auth data
 *
 * @return bool
 * @throws Exception
 */
	protected function _loadSessionFromPersistentData() {
		if (CakeSession::check('Facebook.Auth.accessToken')) {
			$accessToken = CakeSession::read('Facebook.Auth.accessToken');
			$session = new FacebookSession($accessToken);

			//@TODO In Facebook SDK v4.1 FacebookSession::validate() will return false instead of throwing an exception
			$valid = false;
			try {
				$valid = $session->validate();
			} catch (\Facebook\FacebookSDKException $ex) {
				// do nothing
				//@TODO Log that session has expired
			} catch (Exception $ex) {
				throw $ex;
			}

			if ($valid) {
				$this->FacebookSession = $session;
				return true;
			}
		}
		return false;
	}

/**
 * @throws Exception
 */
	protected function _loadSessionFromRedirect() {
		$session = $this->getRedirectLoginHelper()
			->getSessionFromRedirect();

		if ($session) {
			$this->FacebookSession = $session;
			return true;
		}
		return false;
	}

/**
 * Load and persist user info
 *
 * @param bool $force
 */
	protected function _loadUser($force = false) {
		if ($force === true || !CakeSession::check('Facebook.User')) {
			if ($this->FacebookSession) {
				$me = $this->graphGet('/me')->getGraphObject(GraphUser::className());
				$this->_user = $me->asArray();

				CakeSession::write('Facebook.User', $this->_user);
			}
		} else {
			$this->_user = CakeSession::read('Facebook.User');
		}
	}

/**
 * Load and persist user permissions
 *
 * @param bool $force
 */
	protected function _loadUserPermissions($force = false) {
		if ($force === true || !CakeSession::check('Facebook.UserPermissions')) {
			if ($this->FacebookSession) {
				// For legacy apps the default permission 'installed' will be set to 'true'
				//$permissions = array('installed' => true);
				$permissions = array();

				$data = $this->graphGet('/me/permissions')->getResponse()->data;
				array_walk($data, function ($val) use (&$permissions) {
					$permissions[$val->permission] = ($val->status === 'granted') ? true : false;
				});

				$this->_userPermissions = $permissions;
				CakeSession::write('Facebook.UserPermissions', $this->_userPermissions);
			}
		} else {
			$this->_userPermissions = (array)CakeSession::read('Facebook.UserPermissions');
		}
	}

/**
 * Update persistent auth data
 */
	protected function _updateAccessToken() {
		$accessToken = $this->FacebookSession->getAccessToken();
		CakeSession::write('Facebook.Auth.accessToken', (string)$accessToken);
		CakeSession::write('Facebook.Auth.expiresAt', $accessToken->getExpiresAt());
	}

/**
 * Restore FacebookSession and user info
 */
	protected function _restoreSession() {
		if (
		$this->_loadSessionFromPersistentData()
			//|| $this->loadSessionFromJavascriptHelper()
			//|| $this->_loadSessionFromRedirect()
		) {
			$this->_loadUser();
			$this->_loadUserPermissions();
		}
	}

/**
 * Get active FacebookSession instance
 *
 * @return FacebookSession|null
 */
	public function getSession() {
		if ($this->FacebookSession === null) {
			$this->_restoreSession();
		}

		return $this->FacebookSession;
	}

/**
 * Get instance of FacebookRedirectLoginHelper
 *
 * @param $redirectUrl
 * @return FacebookRedirectLoginHelper
 */
	public function getRedirectLoginHelper($redirectUrl = null) {
		$redirectUrl = ($redirectUrl) ?: $this->config['connectUrl'];

		//if ($this->FacebookRedirectLoginHelper === null) {
		$this->FacebookRedirectLoginHelper = new FacebookRedirectLoginHelper(
			Router::url($redirectUrl, true)
		);
		//}
		return $this->FacebookRedirectLoginHelper;
	}

/**
 * @param null|string $redirectUrl
 * @param array|string $scope
 * @param bool $displayAsPopup
 * @return string
 */
	public function getLoginUrl($redirectUrl = null, $scope = array(), $displayAsPopup = false) {
		if (is_string($scope)) {
			$scope = explode(',', $scope);
		}

		// add default permissions
		$scope += $this->config['defaultPermissions'];

		return $this->getRedirectLoginHelper($redirectUrl)
			->getLoginUrl($scope, static::GRAPH_API_VERSION, $displayAsPopup);
	}

	public function getConnectRedirectUrl() {
		return $this->config['connectRedirectUrl'];
	}

/**
 * @param null $next
 * @return string
 */
	public function getLogoutUrl($next = null) {
		$next = ($next) ?: '/';

		return $this->getRedirectLoginHelper()
			->getLogoutUrl($this->FacebookSession, Router::url($next, true));
	}

/**
 * Get Facebook User Id of connected user
 *
 * @return null|string
 */
	public function getUserId() {
		//return $this->FacebookSession->getUserId();
		return $this->getUser('id');
	}

/**
 * Get Facebook User Info of connected user
 *
 * @param null|string $key
 * @return null|mixed
 */
	public function getUser($key = null) {
		if ($key === null) {
			return $this->_user;
		}

		if ($this->_user && isset($this->_user[$key])) {
			return $this->_user[$key];
		}

		return null;
	}

/**
 * Reload user information from Facebook Graph
 */
	public function reloadUser() {
		$this->_loadUser(true);
		$this->_loadUserPermissions(true);
	}

/**
 * Get facebook user permissions
 *
 * @return array
 */
	public function getUserPermissions() {
		// lazy load
		if ($this->_userPermissions === null) {
			$this->_loadUserPermissions();
		}

		return (array)$this->_userPermissions;
	}

	public function getUserPermissionRequestUrl($requestedPerms) {
		$grantedPerms = $this->getUserPermissions();
		$requestedPerms = (array)$requestedPerms;
		$declinedPerms = array();

		// check if any of the requested perms have been revoked previously
		foreach ($requestedPerms as $perm) {
			if (isset($grantedPerms[$perm]) && $grantedPerms[$perm] === false) {
				$declinedPerms[] = $perm;
			}
		}

		// if one of the requested params has been revoked, perform a re-request
		if (!empty($declinedPerms)) {
			return $this->getUserPermissionReRequestUrl($requestedPerms);
		}

		return $this->getLoginUrl(null, $requestedPerms);
	}

	public function getUserPermissionReRequestUrl($requestedPerms = array()) {
		return $this->getRedirectLoginHelper()->getReRequestUrl($requestedPerms);
	}

/**
 * @see FacebookApi::validateUserPermission()
 */
	public function checkUserPermission($permissions) {
		return self::validateUserPermission($this->getUserPermissions(), $permissions);
	}

/**
 * Delete (previously granted) permission
 * Deletes the given permission by issuing a delete request to the GraphApi
 * DELETE /{user-id}/permissions/{permission-name}
 *
 * @see https://developers.facebook.com/docs/facebook-login/permissions/#revoking
 * @param string $perm Permission name
 * @return bool
 */
	public function revokeUserPermission($perm) {
		try {
			$result = $this->graphDelete('/me/permissions/' . (string)$perm);
		} catch (Exception $ex) {
			$result = false;
			$this->log($ex->getMessage(), LOG_WARNING);
		}

		if (!$result) {
			$this->log(__d('facebook', "Failed to delete permission '%s'.", $perm));
			return false;
		}
		$this->log(__d('facebook', "Deleted permission: %s", $perm), 'info');

		// reload permissions
		$this->_loadUserPermissions(true);
		return true;
	}

/**
 * @param $method
 * @param $path
 * @param array $params
 * @return FacebookRequest
 */
	protected function buildGraphRequest($method, $path, $params = array()) {
		return new FacebookRequest($this->getSession(), $method, $path, $params);
	}

/**
 * Execute a Graph Api request
 *
 * @param FacebookRequest $req
 * @return \Facebook\FacebookResponse
 * @throws Facebook\FacebookRequestException
 * @throws Exception
 */
	protected function _executeGraphRequest(FacebookRequest $req) {
		try {
			return $req->execute();

		} catch(FacebookRequestException $ex) {
			// When Facebook returns an error
			$this->log($ex->getMessage(), LOG_ERR);
			throw $ex;
		} catch(\Exception $ex) {
			// When validation fails or other local issues
			$this->log($ex->getMessage(), LOG_ERR);
			throw $ex;
		}
	}

/**
 * Submit a Graph Api Request
 *
 * @param $method
 * @param $path
 * @param $params
 * @return \Facebook\FacebookResponse
 */
	public function graph($method, $path, $params) {
		$req = $this->buildGraphRequest($method, $path, $params);
		return $this->_executeGraphRequest($req);
	}

/**
 * Submit a Graph Api GET Request
 *
 * @param $path
 * @param array $params
 * @return \Facebook\FacebookResponse
 */
	public function graphGet($path, $params = array()) {
		$req = $this->buildGraphRequest('GET', $path, $params);
		return $this->_executeGraphRequest($req);
	}

/**
 * Submit a Graph Api POST Request
 *
 * @param $path
 * @param array $data
 * @return \Facebook\FacebookResponse
 */
	public function graphPost($path, $data = array()) {
		$req = $this->buildGraphRequest('POST', $path, $data);
		return $this->_executeGraphRequest($req);
	}

/**
 * Submit a Graph Api DELETE Request
 *
 * @param $path
 * @return \Facebook\FacebookResponse
 */
	public function graphDelete($path) {
		$req = $this->buildGraphRequest('DELETE', $path);
		return $this->_executeGraphRequest($req);
	}

/**
 * Get Config
 *
 * @param null $key
 * @return array
 */
	public function getConfig($key = null) {
		if ($key === null) {
			return $this->config;
		}

		if (isset($this->config[$key])) {
			return $this->config[$key];
		}

		return null;
	}

/**
 * Log wrapper
 *
 * @param $msg
 * @param $type
 */
	public function log($msg, $type = LOG_DEBUG) {
		if (Configure::read('debug') > 0 && $this->config['debug']) {
			debug($msg);
		}

		if ($this->config['log']) {
			CakeLog::write($type, $msg, static::$logScopes);
		}
	}

/**
 * Get singleton instance
 *
 * @return FacebookApi
 */
	public static function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$class = __CLASS__;
			$instance[0] = new $class();
		}
		return $instance[0];
	}

/**
 * Validate permissions
 *
 * @param array $grantedPerms List of granted permissions
 * @param string|array $checkPerms Comma-separated string or array of permissions to check
 * @return bool|array TRUE if all permissions are granted,
 *                    otherwise array of missing permissions
 */
	public static function validateUserPermission($grantedPerms, $checkPerms) {
		if (is_string($checkPerms)) {
			$checkPerms = explode(',', $checkPerms);
		}

		$missing = array();
		foreach ($checkPerms as $perm) {
			if (!array_key_exists($perm, $grantedPerms) || $grantedPerms[$perm] !== true) {
				$missing[] = $perm;
			}
		}

		return (!empty($missing)) ? $missing : true;
	}

}
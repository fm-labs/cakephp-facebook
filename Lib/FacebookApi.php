<?php

use Facebook\FacebookRequest;
use Facebook\Helpers\FacebookRedirectLoginHelper;

App::uses('CakeSession', 'Model/Datasource');

/**
 * Class FacebookApi
 */
class FacebookApi {

/**
 * CakePHP Facebook API version
 */
	const VERSION = '2.1';

/**
 * Facebook Graph API version
 */
	const GRAPH_API_VERSION = 'v2.10';

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
 * @var Facebook\Facebook
 */
	public $fb;

/**
 * @var FacebookRedirectLoginHelper
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

		CakeSession::start();

		$this->fb = new Facebook\Facebook([
			'app_id'     => $this->config['appId'],
			'app_secret' => $this->config['appSecret'],
			'default_graph_version' => self::GRAPH_API_VERSION
		]);

	}

	/**
	 * Connect with facebook
	 */
	public function connect() {
		debug("CONNECTING WITH FACEBOOK");

		if ($this->_loadSessionFromPersistentData() /* || $this->_loadSessionFromJavascriptHelper() */) {
			debug("FACEBOOK RESUMED");
			return true;
		}

		return ($this->_handleRedirectLogin() /* || $this->_handleJavascriptLogin() */);
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
	protected function _handleRedirectLogin() {
		try {
			$accessToken = $this->getRedirectLoginHelper()->getAccessToken();

			// persist access token
			$this->_useAccessToken($accessToken);

			$this->log(sprintf("Facebook user %s connected", $this->getUserId()));
			return true;

		} catch(Facebook\Exceptions\FacebookResponseException $ex) {
			$this->log("FACEBOOK CONNECT ERROR: " . $ex->getMessage(), 'error');

		} catch(Facebook\Exceptions\FacebookSDKException $ex) {
			$this->log("FACEBOOK CONNECT ERROR: " . $ex->getMessage(), 'error');

		} catch (Exception $ex) {
			$this->log("FACEBOOK CONNECT ERROR: " . $ex->getMessage(), 'error');
		}

		return false;
	}

	protected function _handleJavascriptLogin() {
		/*
		if ($this->_loadSessionFromJavascriptHelper()) {
			// persist access token
			$this->_updateAccessToken();

			// update user info and permissions
			$this->_loadUser(true);
			$this->_loadUserPermissions(true);

			$this->log(sprintf("Facebook user %s connected via javascript", $this->getUserId()));
			return true;
		}
		*/

		return false;
	}
	
/**
 * Disconnect Facebook user from app and cleanup persistent data
 */
	public function disconnect() {
		$this->_user = null;
		$this->_userPermissions = null;
		$this->FacebookRedirectLoginHelper = null;

		CakeSession::delete('Facebook.Auth');
		CakeSession::delete('Facebook.User');
		CakeSession::delete('Facebook.UserPermissions');
		CakeSession::delete('Facebook');
		CakeSession::delete('FBRLH_state');
	}

/**
 * Load facebook access token from persisted auth data
 *
 * @return bool
 * @throws Exception
 */
	protected function _loadSessionFromPersistentData() {
		if (CakeSession::check('Facebook.Auth.accessToken')) {
			$token = CakeSession::read('Facebook.Auth.accessToken');
			$expiresAt = CakeSession::read('Facebook.Auth.accessTokenExp');

			$accessToken = new \Facebook\Authentication\AccessToken($token, $expiresAt);
			$this->fb->setDefaultAccessToken($accessToken);
			/*
			try {
				$this->_useAccessToken($accessToken);
			} catch (\Exception $ex) {
				$this->log("Failed to restore session: " . $ex->getMessage());
			}
			*/
		}
		return false;
	}

/**
 * Load session with JavascriptHelper
 *
 * @return bool
 */
	protected function _loadSessionFromJavascriptHelper() {
		//@TODO: Implement Facebook::_loadSessionFromJavascriptHelper()
		return false;
	}

/**
 * Load and persist user info
 *
 * @throws Exception
 */
	protected function _loadUser() {
		try {
			$params = ['fields' => 'id,name,email,first_name,last_name'];
			$me = $this->graphGet('/me', $params)->getGraphUser();
			$this->_user = $me->asArray();

			CakeSession::write('Facebook.User', $this->_user);
		} catch (\Exception $ex) {
			$this->log("FACEBOOK LOAD USER FAILED: " . $ex->getMessage(), 'error');
			throw $ex;
		}
	}

/**
 * Load and persist user permissions
 *
 * @return void
 */
	protected function _loadUserPermissions() {
		try {
			// For legacy apps the default permission 'installed' will be set to 'true'
			//$permissions = array('installed' => true);
			$permissions = array();

			$data = $this->graphGet('/me/permissions')->getDecodedBody();
			array_walk($data, function ($val) use (&$permissions) {
				$permissions[$val->permission] = ($val->status === 'granted') ? true : false;
			});

			$this->_userPermissions = $permissions;
			CakeSession::write('Facebook.UserPermissions', $this->_userPermissions);

		} catch (\Exception $ex) {
			$this->log("FACEBOOK LOAD USER PERMS FAILED: " . $ex->getMessage(), 'error');
			throw $ex;
		}
	}

/**
 * Use given access token
 *
 * Automatically tries to exchange a short-lived token with a long-lived one.
 *
 * @param $accessToken Facebook\Authentication\AccessToken
 * @throws Facebook\Exceptions\FacebookSDKException
 * @return void
 */
	protected function _useAccessToken($accessToken)
	{
		$this->log('Use accesstoken ' . (string) $accessToken);

		// The OAuth 2.0 client handler helps us manage access tokens
		$oAuth2Client = $this->fb->getOAuth2Client();

		//@TODO Catch request exception
		// Get the access token metadata from /debug_token
		$tokenMetadata = $oAuth2Client->debugToken($accessToken);

		//@TODO App validation
		// Validation (these will throw FacebookSDKException's when they fail)
		//$tokenMetadata->validateAppId($this->config['appId']); // Replace {app-id} with your app id

		//@TODO User validation
		// If you know the user ID this access token belongs to, you can validate it here
		//$tokenMetadata->validateUserId('123');

		// Expiration validation
		$tokenMetadata->validateExpiration(); // throws exception if validation fails

		if (! $accessToken->isLongLived()) {
			// Exchanges a short-lived access token for a long-lived one
			try {
				$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			} catch (Facebook\Exceptions\FacebookSDKException $ex) {
				$this->log("Error getting long-lived access token: " . $ex->getMessage(), 'warning');
			}

			$this->log("Exchanged SL token for LL token: " . $accessToken->getValue());
		}

		$this->fb->setDefaultAccessToken($accessToken);

		// store in session
		CakeSession::write('Facebook.Auth.accessToken', (string) $accessToken);
		CakeSession::write('Facebook.Auth.expiresAt', $accessToken->getExpiresAt());

		$_SESSION['fb_access_token'] = (string) $accessToken; //@todo: Check if direct access to $_SESSION variable is nescessary
	}

/**
 * Update persistent auth data
 *
 * @return void
 * @deprecated Use _setAccessToken instead
 */
	protected function _updateAccessToken() {
	}

/**
 * Get instance of FacebookRedirectLoginHelper
 *
 * @return FacebookRedirectLoginHelper
 */
	public function getRedirectLoginHelper() {
		return $this->FacebookRedirectLoginHelper = $this->fb->getRedirectLoginHelper();
	}

/**
 * @param null|string $redirectUrl
 * @param array|string $scope
 * @param bool $displayAsPopup
 * @return string
 */
	public function getLoginUrl($redirectUrl = null, $scope = array()) {
		if (is_string($scope)) {
			$scope = explode(',', $scope);
		}

		$redirectUrl = ($redirectUrl) ?: $this->config['connectUrl'];
		$redirectUrl = Router::url($redirectUrl, true);

		// add default permissions
		$scope += $this->config['defaultPermissions'];

		return $this->getRedirectLoginHelper()->getLoginUrl($redirectUrl, $scope);
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

		// lazy load
		if ($this->_user === null) {
			$this->_loadUser();
		}

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
		$this->_user = $this->_userPermissions = null;

		$this->_loadUser();
		$this->_loadUserPermissions();
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
			$this->log($ex->getMessage(), 'warning');
		}

		if (!$result) {
			$this->log(__d('facebook', "Failed to delete permission '%s'.", $perm));
			return false;
		}
		$this->log(__d('facebook', "Deleted permission: %s", $perm), 'info');

		// reload permissions
		$this->reloadUser();
		return true;
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

		switch (strtoupper($method)) {
			case "GET":
				return $this->graphGet($path, $params);
			case "POST":
				return $this->graphPost($path, $params);
			case "DELETE":
				return $this->graphDelete($path, $params);
			default:
				throw new InvalidArgumentException('Unsupported graph method: ' . $method);
		}

	}

/**
 * Submit a Graph Api GET Request
 *
 * @param $path
 * @param array $params
 * @return \Facebook\FacebookResponse
 */
	public function graphGet($path, $params = array()) {
		$_path = $path . '?';
		foreach ($params as $k => $v) {
			$_path .= sprintf("%s=%s&", $k, $v);
		}
		return $this->fb->get($_path);
	}

/**
 * Submit a Graph Api POST Request
 *
 * @param $path
 * @param array $data
 * @return \Facebook\FacebookResponse
 */
	public function graphPost($path, $data = array()) {
		return $this->fb->post($path, $data);
	}

/**
 * Submit a Graph Api DELETE Request
 *
 * @param $path
 * @return \Facebook\FacebookResponse
 */
	public function graphDelete($path) {
		return $this->fb->post($path);
	}

	/**
	 * @param $method
	 * @param $path
	 * @param array $params
	 * @return FacebookRequest
	 * @deprecated
	 */
	protected function buildGraphRequest($method, $path, $params = array()) {
		return new FacebookRequest($this->fb->getApp(), $this->fb->getDefaultAccessToken(), $method, $path, $params);
	}

	/**
	 * Execute a Graph Api request
	 *
	 * @param FacebookRequest $req
	 * @return \Facebook\FacebookResponse
	 * @throws Facebook\Exceptions\FacebookResponseException
	 * @throws Facebook\Exceptions\FacebookSDKException
	 * @deprecated
	 */
	protected function _executeGraphRequest(FacebookRequest $req) {
		try {
			return $this->fb->getClient()->sendRequest($req);

		} catch(Facebook\Exceptions\FacebookResponseException $ex) {
			$this->log($ex->getMessage(), 'error');
			throw $ex;

		} catch(Facebook\Exceptions\FacebookSDKException $ex) {
			$this->log($ex->getMessage(), 'error');
			throw $ex;
		}
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
	public function log($msg, $type = 'debug') {
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
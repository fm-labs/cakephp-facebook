<?php
App::uses('FacebookApi', 'Facebook.Lib');
App::uses('Router', 'Routing');
App::uses('Hash', 'Utility');
App::uses('CakeSession', 'Model/Datasource');

/**
 * FacebookConnect
 *
 * Helper library for CakePHP + Facebook PHP SDK
 *
 * Main objectives:
 * * Keep track of facebook user info
 * * Keep track of facebook permissions
 * * Organize facebook user data in session
 * * Access this library from nearly anywhere in the app
 *
 * Dependencies:
 * * /Lib/Api/FacebookApi
 *
 * Classes that depend on FacebookConnect:
 * * /Controller/Component/FacebookComponent
 * * /Controller/Component/Auth/FacebookAuthorize
 * * /View/Helper/FacebookHelper
 *
 */
class FacebookConnect {

	static public $sessionKey = 'Facebook.User';

/**
 * Facebook api instance
 *
 * @var FacebookApi
 */
	public $FacebookApi;

/**
 * Facebook user data
 *
 * @var array
 */
	public $user;

/**
 * Facebook user permissions
 *
 * @var array
 */
	public $perms;

/**
 * @var string
 */
	protected $_accessToken;

/**
 * Get singleton instance
 *
 * @return FacebookConnect
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
 * Static wrapper for getUser()
 *
 * @param null|string $key
 * @return mixed
 */
	public static function user($key = null) {
		$_this = self::getInstance();
		return $_this->getUser($key);
	}

/**
 * Constructor
 * Starts FacebookConnect session
 */
	public function __construct() {
		$this->FacebookApi = FacebookApi::getInstance();
		//$this->connect();

        // restore user info from session, if available
        $this->restoreSession();
	}

/**
 * Get facebook login url
 *
 * @param string|array $redirectUrl Url where facebook redirects after login.
 *                                  Router::url() compatible
 * @param array $scope Facebook permissions
 * @return string Login URL
 */
	public function getLoginUrl($redirectUrl = null, $scope = array()) {
		$params = array();

        $scope = (array)Configure::read('Facebook.scope') + $scope;
		if ($scope) {
			$params['scope'] = (is_array($scope)) ? $scope : explode(',', trim($scope));
		}
		if ($redirectUrl) {
			$params['redirect_uri'] = Router::url($redirectUrl, true);
		}

		return $this->FacebookApi->getLoginUrl($params);
	}

/**
 * Get facebook logout url
 * Requires a valid access_token
 *
 * @param string $redirectUrl Url where facebook redirects after logout.
 *                            Router::url() compatible
 * @return string Logout URL
 */
	public function getLogoutUrl($redirectUrl = null) {
		$params = array();
		if ($redirectUrl) {
			$params['next'] = Router::url($redirectUrl, true);
		}

		return $this->FacebookApi->getLogoutUrl($params);
	}

/**
 * Connect facebook user
 * Uses Facebook api to determine login state.
 * Retrieve and set user data and permissions.
 *
 * @return boolean
 */
	public function connect() {
		$uid = $this->FacebookApi->getUser();
		$accessToken = $this->FacebookApi->getAccessToken();

		// check if accessToken has changed (e.g. after requesting/revoking permissions)
		if (!$this->_accessToken !== $accessToken) {
			// reset without destroying the facebook session
			$this->disconnect(false);
		}

		if ($uid && $this->user) {
			// connected and active session
			// we are all set. just return the cached user data.

			// Check UserIds
			if ($uid === $this->user['id']) {
				//@todo validate accessToken.
				//@todo proposal: store expiration date in session and check token periodically
				return true;
			}

			// UserIds do not match
			// reset without destroying the facebook session
			// and update user info
			$this->disconnect(false);
			$this->log(__d('facebook', 'UserIds do not match. Expected: %s / Actual: %s. Disconnect.',
				$this->user['id'], $uid), 'warning');

		} elseif (!$uid && $this->user) {
			// not connected but active session
			// reset the session
			$this->log(__d('facebook', 'User with ID %s is not connected but has active session. Disconnect.', $uid), 'info');
			$this->disconnect(true);
			return false;

		} elseif (!$uid) {
			// not connected
			return false;
		}


		//@todo confirm identity/verify access token
		//@see https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/#confirm

		// connected but not in session
		// retrieve data from facebook
		$this->updateUserInfo();
		$this->log(__d('facebook', 'Connected user with ID %s', $uid), 'info');

		//@todo dispatch event facebook.connect
		return true;
	}

/**
 * Disconnect app from facebook
 * Deletes the active session.
 * Destroys Facebook session (optional)
 *
 * @see FacebookConnect::disconnect()
 * @param bool $destroyFacebookSession Set to TRUE to destroy facebook session
 * @return void
 */
	public function disconnect($destroyFacebookSession = false) {
		$this->log(__d('facebook', "Disconnecting user %s from facebook (destroy: %s)",
			$this->user('id'), $destroyFacebookSession), 'notice');

		if ($destroyFacebookSession) {
			$this->FacebookApi->destroySession();
		}

		$this->setUser(null);
		$this->setPermissions(array());
		$this->deleteSession();
		//@todo dispatch event facebook.disconnect
	}

/**
 * Refresh user info
 *
 * @todo Deprecate method. Use updateUserInfo() method instead
 */
	public function refresh() {
		$this->disconnect(false);
		$this->connect();

		//@todo dispatch event facebook.refresh
	}

/**
 * Fetch user info and permissions
 */
	public function updateUserInfo() {
		// user profile
		$user = $this->FacebookApi->api('/me');

		// user permissions
		$perms = array();
		$userPerms = $this->FacebookApi->api('/me/permissions');
		if (isset($userPerms['data']) && $userPerms['data'][0]) {
			$perms = $userPerms['data'][0];
		} else {
			$this->log(__d('facebook', 'Failed to parse permissions result'));
		}

		$this->setUser($user);
		$this->setPermissions($perms);
		$this->updateSession();

		//@todo dispatch event facebook.user
	}

/**
 * Restore user info from session
 *
 * @return void
 */
	protected function restoreSession() {
		if (CakeSession::check(self::$sessionKey)) {
			$session = CakeSession::read(self::$sessionKey);
			$this->setUser($session['User']);
			$this->setPermissions($session['Permission']);
			$this->_accessToken = $session['Auth']['access_token'];
		}
	}

/**
 * Store user info in session
 *
 * @return void
 */
	protected function updateSession() {
		if (!$this->user) {
			return;
		}

		CakeSession::write(self::$sessionKey, array(
            'Auth' => array(
                'access_token' => $this->FacebookApi->getAccessToken(),
                //'now' => time(),
                //'expire_in' => time() + HOUR
            ),
			'User' => $this->user,
			'Permission' => $this->perms,
		));
	}

/**
 * Delete user info from session
 *
 * @return void
 */
	protected function deleteSession() {
		CakeSession::delete(self::$sessionKey);
	}

/**
 * Set facebook user data
 *
 * @param $user
 * @return $this
 */
	protected function setUser($user) {
		$this->user = $user;
		return $this;
	}

/**
 * Get facebook user data
 *
 * @param $key
 * @return array Returns facebook user data or NULL if no user connected
 */
	public function getUser($key = null) {
		if (!$this->user || $key === null) {
			return $this->user;
		}

		return Hash::get($this->user, $key);
	}

/**
 * Set facebook permissions
 *
 * @param $perms
 * @return $this
 */
	protected function setPermissions($perms) {
		$this->perms = $perms;
		return $this;
	}

/**
 * Get facebook user permissions
 *
 * @return array
 */
	public function getPermissions() {
		return $this->perms;
	}

/**
 * Check permission(s)
 *
 * @param string|array $perms Comma-separated string or array of permissions to check
 * @return bool|array TRUE if all permissions are granted,
 *                    otherwise array of missing permissions
 */
	public function checkPermission($perms) {
		if (is_string($perms)) {
			$perms = explode(',', $perms);
		}

		$grantedPerms =& $this->perms;
		$missing = array();
		foreach ($perms as $perm) {
			if (!array_key_exists($perm, $grantedPerms) || $grantedPerms[$perm] != true) {
				$missing[] = $perm;
			}
		}

		return (!empty($missing)) ? $missing : true;
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
	public function deletePermission($perm) {
		$result = $this->FacebookApi->api('/me/permissions/' . (string)$perm, 'DELETE');
		if ($result != 'true') {
			$this->log(
				__d('facebook', "Failed to delete permission '%s'. Returned result: %s", $perm, $result));
			return false;
		}

		if (isset($this->perms[$perm])) {
			unset($this->perms[$perm]);
		}
		$this->log(__d('facebook', "Deleted permission: %s", $perm), 'info');
		return true;
	}

/**
 * Log wrapper
 *
 * @see CakeLog::write()
 * @param string $msg Log message
 * @param string $type Log type
 * @return boolean
 */
	protected function log($msg, $type = 'error') {
		return CakeLog::write($type, $msg, array('facebook'));
	}

}
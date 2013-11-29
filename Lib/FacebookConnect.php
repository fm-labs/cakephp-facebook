<?php
App::uses('FacebookApi', 'Facebook.Lib');
App::uses('Router','Routing');
App::uses('Hash','Utility');

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
 * Libraries that depend on FacebookConnect:
 * * /Controller/Component/FacebookComponent
 * * /Controller/Component/Auth/FacebookAuthorize
 * * /View/Helper/FacebookHelper
 *
 */
class FacebookConnect {
	
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
        if ($scope) {
            $params['scope'] = (is_array($scope)) ? $scope : explode(',',trim($scope));
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

        // connected and active session
        // we are all set. just return the cached user data.
        if ($uid && $this->user) {
            //@todo check if uids match
            //@todo validate accessToken.
            //@todo store expiration date in session and check token periodically
            return true;
        }
        // not connected but active session
        // reset the session
        elseif (!$uid && $this->user) {
            $this->log(__d('facebook','User with ID %s is not connected but has active session. Disconnect.',$uid),'info');
            $this->disconnect(true);
            return false;
        }
        // connected but not in session
        // retrieve data from facebook
        elseif ($uid) {
            //@todo confirm identity/verify access token
            //@see https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/#confirm

            $this->log(__d('facebook','Connected user with ID %s',$uid),'info');
            $this->updateUserInfo();

            //@todo dispatch event facebook.connect
            return true;
        }
        return false;
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
    public function disconnect($destroyFacebookSession = true) {

        $this->log(__d('facebook',"Disconnecting user %s from facebook (destroy: %s)",
            $this->user('id'), $destroyFacebookSession),'notice');

        if ($destroyFacebookSession) {
            $this->FacebookApi->destroySession();
        }

        $this->setUser(null);
        $this->setPermissions(array());
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
            $this->log(__d('facebook','Failed to parse permissions result'));
        }

        $this->setUser($user);
        $this->setPermissions($perms);

        //@todo dispatch event facebook.user
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

		if (!$this->user || $key === null)
			return $this->user;
		
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

        if (is_string($perms))
            $perms = explode(',',$perms);

        $grantedPerms =& $this->perms;
        $missing = array();
        foreach($perms as $perm) {
            if (!array_key_exists($perm, $grantedPerms) || $grantedPerms[$perm] != true) {
                $missing[] = $perm;
            }
        }

        if (empty($missing))
            return true;

        return $missing;
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
        $result = $this->FacebookApi->api('/me/permissions/'.(string) $perm, 'DELETE');
        if ($result != 'true') {
            $this->log(__d('facebook',"Failed to delete permission '%s'. Returned result: %s", $perm, $result));
            return false;
        }

        if (isset($this->perms[$perm])) {
            unset($this->perms[$perm]);
        }
        $this->log(__d('facebook',"Deleted permission: %s", $perm), 'info');
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
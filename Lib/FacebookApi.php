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
    CONST VERSION = '2.0';

/**
 * Facebook Graph API version
 */
    CONST GRAPH_API_VERSION = FacebookRequest::GRAPH_API_VERSION;

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
        // Internal connect URL
        'connectUrl' => '/facebook/auth/connect/',
        // Default Login permissions
        'permissions' => array('email')
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
    protected $user;

/**
 * @var array
 */
    protected $userPermissions;

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
 * Connect Facebook user
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
            if ($this->loadSessionFromRedirect()) {

                $this->updateAccessToken();
                $this->loadUser(true);
                $this->loadUserPermissions(true);

                return true;
            }
        } catch (Exception $ex) {
            $this->log("CONNECT ERROR: " . $ex->getMessage());
        }
    }

/**
 * Disconnect Facebook user from app and cleanup persistent data
 */
    public function disconnect() {
        $this->user = null;
        $this->userPermissions = null;
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
    protected function loadSessionFromPersistentData() {
        if (CakeSession::check('Facebook.Auth.accessToken')) {
            $accessToken = CakeSession::read('Facebook.Auth.accessToken');
            $session = new FacebookSession($accessToken);

            //@TODO In Facebook SDK v4.1 validate() will return false instead of throwing an exception
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
    }

/**
 * @throws Exception
 */
    protected function loadSessionFromRedirect() {
        $session = $this->getRedirectLoginHelper()
            ->getSessionFromRedirect();

        if ($session) {
            $this->FacebookSession = $session;
            return true;
        }
    }

/**
 * Load and persist user info
 *
 * @param bool $force
 */
    protected function loadUser($force = false) {
        if ($force === true || !CakeSession::check('Facebook.User')) {
            if ($this->FacebookSession) {
                $me = $this->graphGet('/me')->getGraphObject(GraphUser::className());
                $this->user = $me->asArray();

                CakeSession::write('Facebook.User', $this->user);
            }
        } else {
            $this->user = CakeSession::read('Facebook.User');
        }
    }

/**
 * Load and persist user permissions
 *
 * @param bool $force
 */
    protected function loadUserPermissions($force = false) {
        if ($force === true || !CakeSession::check('Facebook.UserPermissions')) {
            if ($this->FacebookSession) {
                // For legacy apps the default permission 'installed' will be set to 'true'
                //$permissions = array('installed' => true);
                $permissions = array();

                $data = $this->graphGet('/me/permissions')->getResponse()->data;
                array_walk($data, function ($val) use (&$permissions) {
                    $permissions[$val->permission] = ($val->status === 'granted') ? true : false;
                });

                $this->userPermissions = $permissions;
                CakeSession::write('Facebook.UserPermissions', $this->userPermissions);
            }
        } else {
            $this->userPermissions = (array) CakeSession::read('Facebook.UserPermissions');
        }
    }

/**
 * Update persistent auth data
 */
    protected function updateAccessToken() {
        $accessToken = $this->FacebookSession->getAccessToken();
        CakeSession::write('Facebook.Auth.accessToken', (string) $accessToken);
        CakeSession::write('Facebook.Auth.expiresAt', $accessToken->getExpiresAt());
    }

/**
 * Restore FacebookSession and user info
 */
    protected function restoreSession() {
        if (
            $this->loadSessionFromPersistentData()
            //|| $this->loadSessionFromJavascriptHelper()
            //|| $this->loadSessionFromRedirect()
        ) {
            $this->loadUser();
            $this->loadUserPermissions();
        }
    }

/**
 * Get active FacebookSession instance
 *
 * @return FacebookSession|null
 */
    public function getSession() {
        if ($this->FacebookSession === null) {
            $this->restoreSession();
        }

        return $this->FacebookSession;
    }

/**
 * Get instance of FacebookRedirectLoginHelper
 * 
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
        $scope += $this->config['permissions'];

        return $this->getRedirectLoginHelper($redirectUrl)
            ->getLoginUrl($scope, static::GRAPH_API_VERSION, $displayAsPopup);
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
            return $this->user;
        }

        if ($this->user && isset($this->user[$key])) {
            return $this->user[$key];
        }

        return null;
    }

/**
 * Reload user information from Facebook Graph
 */
    public function reloadUser() {
        $this->loadUser(true);
        $this->loadUserPermissions(true);
    }

/**
 * Get facebook user permissions
 *
 * @return array
 */
    public function getUserPermissions() {
        // lazy load
        if ($this->userPermissions === null) {
            $this->loadUserPermissions();
        }

        return (array) $this->userPermissions;
    }

    public function getUserPermissionRequestUrl($requestedPerms) {
        $grantedPerms = $this->getUserPermissions();
        $requestedPerms = (array) $requestedPerms;
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
        $this->loadUserPermissions(true);
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
    protected function executeGraphRequest(FacebookRequest $req) {
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
        return $this->executeGraphRequest($req);
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
        return $this->executeGraphRequest($req);
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
        return $this->executeGraphRequest($req);
    }

/**
 * Submit a Graph Api DELETE Request
 *
 * @param $path
 * @return \Facebook\FacebookResponse
 */
    public function graphDelete($path) {
        $req = $this->buildGraphRequest('DELETE', $path);
        return $this->executeGraphRequest($req);
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

/**
 * Log wrapper
 *
 * @param $msg
 * @param $type
 * @return bool
 */
    public static function log($msg, $type = LOG_DEBUG) {
        if (Configure::read('debug') > 0) {
            debug($msg);
        }
        return CakeLog::write($type, $msg, static::$logScopes);
    }

}
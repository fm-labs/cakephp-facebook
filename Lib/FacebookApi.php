<?php
class FacebookApi {

    const API_VERSION_V1 = 1;
    const API_VERSION_V2 = 2;

    public static $version = self::API_VERSION_V2;

/**
 * @var array
 */
	public $config;

/**
 * @var Facebook
 */
	public $FB;

/**
 * Constructor
 *
 * @throws Exception
 */
	public function __construct() {
		if (!class_exists('Facebook')) {
			throw new Exception('Facebook PHP SDK not found');
		}

		if (!Configure::read('Facebook')) {
			throw new Exception('Facebook configuration not loaded');
		}

        // @todo implement other config params
		$this->config = array(
			//BaseFacebook params
			'appId' => Configure::read('Facebook.appId'),
			'secret' => Configure::read('Facebook.appSecret'),
			//'fileUpload' => false,
			//'trustForwarded' => false,
			//'allowSignedRequest'=>true,

			//Facebook params
			//'sharedSession' => null,

			//Custom params
			//'log' => true,
		);

        if (self::$version === self::API_VERSION_V2) {
            Facebook::$DOMAIN_MAP = array(
                'api'         => 'https://api.facebook.com/v2.0/',
                'api_video'   => 'https://api-video.facebook.com/v2.0/',
                'api_read'    => 'https://api-read.facebook.com/v2.0/',
                'graph'       => 'https://graph.facebook.com/v2.0/',
                'graph_video' => 'https://graph-video.facebook.com/v2.0/',
                'www'         => 'https://www.facebook.com/v2.0/',
            );
        }

		$this->FB = new Facebook($this->config);
	}

/**
 * Wrapper for Facebook methods
 *
 * @param $method
 * @param $params
 * @return mixed
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->FB, $method), $params);
	}

/**
 * Get singleton instance
 *
 * @return FacebookApi
 */
	public static function &getInstance() {
		static $fbinstance = array();
		if (!$fbinstance) {
			$class = __CLASS__;
			$fbinstance[0] = new $class();
		}
		return $fbinstance[0];
	}

/**
 * Static Wrapper
 * Call Facebook methods statically
 *
 * @param $method
 * @param $params
 * @return mixed
 */
	public static function __callStatic($method, $params) {
		$_this = FacebookApi::getInstance();
		return call_user_func_array(array($_this->FB, $method), $params);
	}

    public static function getDomainUrl($domain, $url = '') {
        $map = Facebook::$DOMAIN_MAP;
        if (!isset($map[$domain])) {
            return false;
        }
        return $map[$domain] . $url;
    }

/**
 * Get AppAccessToken
 *
 * @return string Access Token
 */
	public static function getAppAccessToken() {
		$appId = Configure::read('Facebook.appId');
		$appSecret = Configure::read('Facebook.appSecret');

		$appTokenUrl = self::getDomainUrl('graph', "oauth/access_token");
		$appTokenUrl .= sprintf("?client_id=%s&client_secret=%s", $appId, $appSecret);
		$appTokenUrl .= "&grant_type=client_credentials";

		$response = file_get_contents($appTokenUrl);
		$params = null;
		parse_str($response, $params);

		if (isset($params['access_token'])) {
			return $params['access_token'];
		}
		return false;
	}

/**
 * Debug Token
 *
 * @param string $inputToken Facebook Access Token
 * @param string $appAccessToken Facebook App Access Token
 * @return array
 */
	public static function debugToken($inputToken, $appAccessToken = null) {
		if ($appAccessToken === null) {
			$appAccessToken = self::getAppAccessToken();
		}

		$url = self::getDomainUrl('graph', "debug_token");
		$url .= "?input_token=" . $inputToken;
		$url .= "&access_token=" . $appAccessToken;

		$response = file_get_contents($url);
		$response = json_decode($response, true);
		return $response;
	}

/**
 * Log wrapper
 *
 * @param $msg
 * @deprecated
 */
	public static function errorLog($msg) {
		Facebook::errorLog($msg);
		CakeLog::error($msg, array('facebook'));
	}

}
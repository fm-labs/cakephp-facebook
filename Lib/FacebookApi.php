<?php
class FacebookApi extends Facebook {

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

	public function __construct() {
		if (!class_exists('Facebook')) {
			throw new Exception('Facebook library not found');
		}

		if (!Configure::read('Facebook')) {
			throw new Exception('Facebook configuration is not present');
		}

		$config = array(
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

		parent::__construct($config);
	}

/**
 * Get AppAccessToken
 *
 * @return string Access Token
 */
	public static function getAppAccessToken() {
		$appId = Configure::read('Facebook.appId');
		$appSecret = Configure::read('Facebook.appSecret');

		$appTokenUrl = "https://graph.facebook.com/oauth/access_token";
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

		$url = "https://graph.facebook.com/debug_token?";
		$url .= "input_token=" . $inputToken;
		$url .= "&access_token=" . $appAccessToken;

		$response = file_get_contents($url);
		$response = json_decode($response, true);
		return $response;
	}

/**
 * Log wrapper
 *
 * @param $msg
 */
	public static function errorLog($msg) {
		parent::errorLog($msg);

		CakeLog::error($msg, array('facebook'));
	}

	/*
	static public function __callstatic($method, $params) {
		die($method);
		$_this = FacebookApi::getInstance();
		return call_user_func_array(array($_this,$method), $params);
	}
	*/

}
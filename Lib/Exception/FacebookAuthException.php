<?php
/**
 * Class FacebookAuthException
 *
 * @deprecated
 */
class FacebookAuthException extends Exception {

	protected $_loginUrl;

	public function __construct($loginUrl) {
		$this->_loginUrl = $loginUrl;

		parent::__construct('Facebook Auth Exception');
	}

	public function getLoginUrl() {
		return $this->_loginUrl;
	}

}
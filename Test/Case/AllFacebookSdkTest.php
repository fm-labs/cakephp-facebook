<?php
App::import('Vendor','Facebook.Facebook',true,array(),'facebook-php-sdk/src/facebook.php');

class AllFacebookSdkTest extends PHPUnit_Framework_TestSuite {

	/**
	 * suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {

        /*
         * WORKAROUND > Failed asserting that an array is empty."
         *
         * Empty the request array to pass PHPSDKTestCase::testGetUserWithoutCodeOrSignedRequestOrSession
         * otherwise failure:
         * "GET, POST, and COOKIE params exist even though they should.
         * Test cannot succeed unless all of $_REQUEST is empty.
         */
        $_REQUEST = array();
		
		$vendorTestDir = App::pluginPath('Facebook').DS.'Vendor'.DS.'facebook'.DS.'php-sdk'.DS.'tests'.DS;

        require_once($vendorTestDir.'bootstrap.php');
		$suite = new PHPUnit_Framework_TestSuite('All Error handling tests');
		$suite->addTestFile($vendorTestDir.'tests.php');
		return $suite;
	}
}
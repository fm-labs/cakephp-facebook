<?php
App::import('Vendor','Facebook.Facebook',true,array(),'facebook-php-sdk/src/facebook.php');

class AllFacebookTest extends PHPUnit_Framework_TestSuite {

	/**
	 * suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		
		$caseDir = dirname(__FILE__).DS;
		
		$suite = new CakeTestSuite('All facebook plugin tests');
		$suite->addTestDirectoryRecursive($caseDir.'Lib');
        $suite->addTestDirectoryRecursive($caseDir.'Model');
		$suite->addTestDirectoryRecursive($caseDir.'View');
		return $suite;
	}
}
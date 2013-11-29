<?php
App::uses('FacebookApi','Facebook.Lib');

class FacebookApiTest extends CakeTestCase {

    public function setUp() {

        Configure::write('Facebook',array(
            'appId' => '123345',
            'appSecret' => 'supersecret'
        ));
    }

	public function testConstruct() {

        $fb = new TestFacebookApi();

		$this->assertIsA($fb, 'Facebook');
        $this->assertEqual($fb->getAppId(), Configure::read('Facebook.appId'));
        $this->assertEqual($fb->getAppSecret(), Configure::read('Facebook.appSecret'));

	}

	public function testGetUser() {

        $this->skipIf(true, 'Test me');
		$fb = new TestFacebookApi(null);
		$user = $fb->getUser();
	}
	
	public function testGetMe() {

        $this->skipIf(true, 'Test me');

		/*
		$fb = new TestFacebookApi(null);
		$user = $fb->getUser();
		
		if ($user) {
			try {
				$me = $fb->api('/me');
				debug($me);
			} catch(FacebookApiException $e) {
				debug($e->getMessage());
				$user = null;
			}
		} else {
			$loginUrl = $fb->getLoginUrl();
			debug($loginUrl);
		}
		*/
	}
}

class TestFacebookApi extends FacebookApi {

}
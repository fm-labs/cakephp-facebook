<?php
App::uses('FacebookConnect','Facebook.Lib');

class TestFacebookConnectData {
	
	public static $data = array('Facebook' => array(
			'User' => array(
				'id' => '99',
				'name' => 'Test User',
				'first_name' => 'Test',
				'last_name' => 'User',
				'link' => 'http://www.facebook.com/test.user',
				'username' => 'test.user',
				'bio' => '.:reality is negotiable:.',
				'education' => array(
						0 => array(
								'school' => array(
										'id' => '1234567890',
										'name' => 'University of Awesome'
								),
								'type' => 'College'
						)
				),
				'gender' => 'female',
				'interested_in' => array(
						0 => 'female'
				),
				'email' => 'testuser@example.com',
				'timezone' => 2,
				'locale' => 'en_US',
				'verified' => true,
				'updated_time' => '2013-04-19T17:20:04+0000'
			),
			'Permissions' => array(
				0 => 'installed',
				1 => 'email',
				2 => 'user_relationship_details',
				3 => 'user_likes',
				4 => 'user_activities',
				5 => 'user_education_history',
				6 => 'user_work_history',
				7 => 'user_online_presence',
				8 => 'user_website',
				9 => 'user_groups',
				10 => 'user_photos',
				11 => 'user_notes',
				12 => 'user_about_me',
				13 => 'user_status'
			)
	));
}

class FacebookConnectTest extends CakeTestCase {
	

	public function setUp() {
        parent::setUp();
	}
	
	public function testConstruct() {
        $this->skipIf(true, 'Test me');
	}

}

class TestFacebookConnect extends FacebookConnect {
	
}



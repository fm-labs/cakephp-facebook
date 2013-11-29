<?php
App::uses('FacebookHelper','Facebook.View/Helper');
App::uses('View','View');
App::uses('Controller','Controller');

class FacebookHelperTest extends CakeTestCase {

	public $FacebookHelper;
	
	public function setUp() {
		parent::setUp();
		
		$View = new View(new Controller);
		
		$this->FacebookHelper = new TestFacebookHelper($View);
	}
	
	public function testRenderType() {
		
		// test default render type (html5)
		$this->assertEqual($this->FacebookHelper->renderType(null), FacebookHelper::RENDER_TYPE_HTML5);
		
		// setter returns instance
		$result = $this->FacebookHelper->renderType(FacebookHelper::RENDER_TYPE_IFRAME);
		$this->assertIsA($result, 'FacebookHelper');
		
		// getter
		$result = $this->FacebookHelper->renderType(null);
		$this->assertEqual($result, FacebookHelper::RENDER_TYPE_IFRAME);
	}
	
	public function testSdk() {

        $this->skipIf(true, 'Implement me');
        return;

		Configure::write('Facebook.appId','1234567890');

        $result = $this->FacebookHelper->sdk();
		$expected = <<<SDK
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=1234567890";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>';
SDK;
        debug($result);
		$this->assertEqual($result, $expected);
	}
	
	public function testUserImageUrl() {
		$result = $this->FacebookHelper->userImageUrl('123');
		$expected = 'http://graph.facebook.com/123/picture';
		$this->assertEqual($result, $expected);
	}
	
	public function testUserImage() {
		$result = $this->FacebookHelper->userImage('123');
		$expected = '<img src="http://graph.facebook.com/123/picture" alt="" />';
		$this->assertEqual($result, $expected);
	}
	
	public function testParseAttributesHtml5() {
		
		$options = array(
			'test' => 'val',
			'test-key' => 'val',
			'underscore_test_key' => 'val',
			'nullkey' => null 	
		);
		$result = $this->FacebookHelper->parseAttributesHtml5($options);
		$expected = array(
			'data-test' => 'val',
			'data-test-key' => 'val',
			'data-underscore-test-key' => 'val',
		);
		$this->assertEqual($result, $expected);
	}
	
	public function testParseAttributesXfbml() {
	
		$options = array(
				'test' => 'val',
				'test-key' => 'val',
				'underscore_test_key' => 'val',
				'nullkey' => null
		);
		$result = $this->FacebookHelper->parseAttributesXfbml($options);
		$expected = array(
				'test' => 'val',
				'test-key' => 'val',
				'underscore-test-key' => 'val',
		);
		$this->assertEqual($result, $expected);
	}
	
	public function testRenderWidget() {
		
		$options = array('test-key' => true, 'under_score_key' => 'val');
		$result = $this->FacebookHelper->renderWidget('fb:test-widget', $options);
		$expected = '<div data-test-key="true" data-under-score-key="val" class="fb-test-widget"></div>';
		$this->assertEqual($result, $expected);
	}
		
	public function testLoginButton() {
		
		$result = $this->FacebookHelper->loginButton();
		$expected = '<div data-show-faces="false" data-width="200" data-max-rows="1" class="fb-login-button"></div>';
		$this->assertEqual($result, $expected);
		
	}
}

class TestFacebookHelper extends FacebookHelper {
	
	public function parseAttributesHtml5($options) {
		return $this->_parseAttributesHtml5($options);
	}
	
	public function parseAttributesXfbml($options) {
		return $this->_parseAttributesXfbml($options);
	}
	
	public function renderWidget($type, $options) {
		return $this->_renderWidget($type, $options);
	}
}
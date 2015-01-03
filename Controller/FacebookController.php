<?php
App::uses('FacebookAppController','Facebook.Controller');

/**
 * @property FacebookUser $FacebookUser
 * @property FacebookComponent $Facebook
 * @property AuthComponent $Auth
 */
class FacebookController extends FacebookAppController {

	public $components = array('Session', 'Facebook.Facebook');

	public $uses = array();

/**
 * @see Controller::beforeFilter()
 * @throws CakeException
 */
	public function beforeFilter() {
		parent::beforeFilter();

		if ($this->Components->enabled('Auth')) {
			$this->Auth->allow('index');
		}
	}

    public function index() {
        $user = $this->Facebook->user();
        debug($user);
        debug($this->Session->read());
    }
}
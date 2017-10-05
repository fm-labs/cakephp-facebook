<?php
App::uses('FacebookAppController', 'Facebook.Controller');

/**
 * Class FacebookDebugController
 */
class FacebookDebugController extends FacebookAppController {

/**
 * @see Controller::beforeFilter()
 * @throws NotFoundException
 */
	public function beforeFilter() {
		parent::beforeFilter();

		if (Configure::read('debug') < 1 || Configure::read('Facebook.debug') !== true) {
			throw new NotFoundException();
		}

		$this->Auth->allow();
	}

/**
 * Index action
 */
	public function index() {
	}

/**
 * Debug access token action
 */
	public function access_token() {
		if ($this->request->is('post') && isset($this->request->data['accessToken'])) {
			$this->Session->write('Facebook.Auth.accessToken', $this->request->data['accessToken']);
			$this->Session->setFlash('Updated Facebook Auth Token to: ' . $this->request->data['accessToken']);
			$this->redirect(array('action' => 'token'));
		} else {
			$this->request->data = $this->Session->read('Facebook.Auth');
		}
	}
} 
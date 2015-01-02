<?php
App::uses('FacebookAppModel', 'Facebook.Model');

class FacebookGraph extends FacebookAppModel {

	public function find($type = 'first', $query = array()) {
		return $this->get($type, $query);
	}

	public function get($path, $params) {
		return $this->getDataSource()->query($this, $path, 'GET', $params);
	}

	public function post($path, $params = array()) {
		return $this->getDataSource()->query($this, $path, 'POST', $params);
	}

	public function remove($path, $params = array()) {
		return $this->getDataSource()->query($this, $path, 'DELETE', $params);
	}
}
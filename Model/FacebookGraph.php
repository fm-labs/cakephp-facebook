<?php
App::uses('FacebookAppModel', 'Facebook.Model');

/**
 * Class FacebookGraph
 *
 * !!! DRAFT !!!
 * !!! DO NOT USE IN PRODUCTION !!!
 */
class FacebookGraph extends FacebookAppModel {

	public function find($type = 'first', $query = array()) {
		return $this->get($type, $query);
	}

	public function get($path, $params) {
		//@DEPRECATED
		return $this->getDataSource()->query($this, $path, 'GET', $params);
	}

	public function post($path, $params = array()) {
		//@DEPRECATED
		return $this->getDataSource()->query($this, $path, 'POST', $params);
	}

	public function remove($path, $params = array()) {
		//@DEPRECATED
		return $this->getDataSource()->query($this, $path, 'DELETE', $params);
	}

	public function read($fields = null, $id = null) {
		//@TODO
		return false;
	}

	public function save($data = null, $validate = true, $fieldList = array()) {
		//@TODO
		return false;
	}

	public function delete($id = null, $cascade = true) {
		//@TODO
		return false;
	}
}
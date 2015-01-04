<?php
App::uses('FacebookAppModel', 'Facebook.Model');

/**
 * Class FacebookGraph
 *
 * !!! DRAFT !!!
 * !!! DO NOT USE IN PRODUCTION !!!
 */
class FacebookGraph extends FacebookAppModel {

    //@REFACTOR
	public function find($type = 'first', $query = array()) {
		return $this->get($type, $query);
	}

    //@DEPRECATED
	public function get($path, $params) {
		return $this->getDataSource()->query($this, $path, 'GET', $params);
	}

    //@DEPRECATED
	public function post($path, $params = array()) {
		return $this->getDataSource()->query($this, $path, 'POST', $params);
	}

    //@DEPRECATED
	public function remove($path, $params = array()) {
		return $this->getDataSource()->query($this, $path, 'DELETE', $params);
	}

    //@TODO
    public function read($fields = null, $id = null) {
        return false;
    }

    //@TODO
    public function save($data = null, $validate = true, $fieldList = array()) {
        return false;
    }

    //@TODO
    public function delete($id = null, $cascade = true) {
        return false;
    }
}
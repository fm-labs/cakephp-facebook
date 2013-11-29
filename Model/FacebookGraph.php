<?php 
App::uses('FacebookAppModel','Facebook.Model');

class FacebookGraph extends FacebookAppModel {
	
	public function find($path, $params = array()) {
		return $this->get($path, $params);
	}

    public function get($path, $params) {
        return $this->getDataSource()->query($this, $path, 'GET', $params);
    }

    public function post($path, $params = array()) {
        return $this->getDataSource()->query($this, $path, 'POST', $params);
    }

    public function delete($path, $params = array()) {
        return $this->getDataSource()->query($this, $path, 'DELETE', $params);
    }
}
<?php 
App::uses('FacebookGraph','Facebook.Model');

class FacebookUser extends FacebookGraph {
	
	public function me() {
		return $this->find('/me');
	}
}
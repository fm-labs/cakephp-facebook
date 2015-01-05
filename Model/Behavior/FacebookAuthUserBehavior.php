<?php
/**
 * Class FacebookAuthUserBehavior
 */
class FacebookAuthUserBehavior extends ModelBehavior {

/**
 * @var array
 */
	protected $_defaultSettings = array(
		'fields' => array(
			'facebook_uid' => 'facebook_uid'
		),
		'sync' => true,
	);

/**
 * @param Model $Model
 * @param array $settings
 */
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array_merge($this->_defaultSettings, $settings);
		}
	}

/**
 *
 * @param Model     $Model
 * @param int       $uid Facebook UID
 * @param array     $params Model find() compatible params
 * @return mixed
 */
	public function findByFacebookUserId(Model $Model, $uid, $params = array()) {
		$scope = array(
			$Model->alias . '.' . $this->settings[$Model->alias]['fields']['facebook_uid'] => $uid,
		);

		if (isset($params['conditions']) && !empty($params['conditions'])) {
			$params['conditions'] = array_merge($params['conditions'], $scope);
		} else {
			$params['conditions'] = $scope;
		}
		return $Model->find('first', $params);
	}

/**
 * Retrieve the Model ID by Facebook user info
 *
 * Override method in subclass or Model class
 * for a more sophisticated synchronisation mechanism
 *
 * @param Model $Model
 * @param $fbUser
 * @return int User model Id
 */
	public function findFacebookUser(Model $Model, $fbUser) {
		$user = $this->findByFacebookUserId($Model, $fbUser['id'], array('recursive' => -1));
		if ($user) {
			return $user[$Model->alias][$Model->primaryKey];
		}

		if ($this->settings[$Model->alias]['sync'] === true) {
			return $this->syncFacebookUser($Model, $fbUser);
		}

		return false;
	}

/**
 * Attempt to sync Facebook user info
 *
 * Override method in subclass or Model class
 * for a more sophisticated synchronisation mechanism
 *
 * @param Model $Model
 * @param $fbUser
 * @return bool
 */
	public function syncFacebookUser(Model $Model, $fbUser) {
		return $this->_addFacebookUser($Model, $fbUser);
	}

/**
 * Attempt to create a new model entry by Facebook user info
 *
 * Override method in subclass or Model class
 * for a more sophisticated synchronisation mechanism
 *
 * @param Model $Model
 * @param $fbUser
 * @return bool
 */
	protected function _addFacebookUser(Model $Model, $fbUser) {
		// Extract facebook UID
		$fbUserId = $fbUser['id'];
		unset($fbUser['id']);

		// Use facebook user info as user data
		$user = $fbUser;
		$user[$this->settings[$Model->alias]['fields']['facebook_uid']] = $fbUserId;

		// Add flag
		//$user['_facebook'] = true;

		// Push
		$Model->create(array($Model->alias => $user));
		if (!$Model->save()) {
			return false;
		}

		return $Model->id;
	}

} 
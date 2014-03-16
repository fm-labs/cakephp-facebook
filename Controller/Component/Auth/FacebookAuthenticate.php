<?php
App::uses('BaseAuthenticate', 'Controller/Component/Auth');

class FacebookAuthenticate extends BaseAuthenticate {

/**
 * Settings for this object.
 *
 * Default settings:
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The model name of the User, defaults to User.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `array('User.is_active' => 1).`
 * - `recursive` The value of the recursive key passed to find(). Defaults to 0.
 * - `contain` Extra models to contain and store in session.
 *
 * FacebookAuth settings:
 * - `defaultPermissions` The default facebook login scopes
 *
 * @var array
 */
	public $settings = array(
		'userModel' => 'User',
		'scope' => array(), // not recommended
		'recursive' => 0,
		'contain' => null,
		'defaultPermissions' => array('email'),
	);

/**
 * @see BaseAuthenticate::authenticate()
 */
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		return $this->getUser($request);
	}

/**
 * @see BaseAuthenticate::getUser()
 */
	public function getUser(CakeRequest $request) {
		$fbUser = FacebookConnect::user();

		// No facebook user is connected
		if (!$fbUser) {
			return false;
		}

		// @todo check default permissions

		// A facebook user is connected, but we won't use a userModel
		// to store/sync user data. So the facebook user info is used
		// as application user data
		if (!$this->settings['userModel']) {
			return $fbUser;
		}

		// Retrieve user data for fb-user from model
		/*
		 * {
			  "id": "123456789",
			  "name": "John Doe",
			  "first_name": "John",
			  "last_name": "Doe",
			  "link": "https://www.facebook.com/john.doe",
			  "username": "john.doe",
			  "gender": "male",
			  "timezone": 1,
			  "locale": "en_US",
			  "verified": true,
			  "updated_time": "2013-01-01T22:22:22+0000"
			}
		 */
		$Model = ClassRegistry::init($this->settings['userModel']);
		if (method_exists($Model, 'getFacebookUser')) {
			$user = call_user_func(array($Model, 'getFacebookUser'), $fbUser, $this->settings);
		} else {
			$user = $this->_getFacebookUser($Model, $fbUser);
		}

		return $user;
	}

	protected function _getFacebookUser(Model $Model, $fbUser) {
		$conditions = array(
			$Model->alias . '.facebook_uid' => $fbUser['id']
		);

		if (!empty($this->settings['scope'])) {
			$conditions = array_merge($conditions, $this->settings['scope']);
		}
		$result = $Model->find('first', array(
			'conditions' => $conditions,
			'recursive' => $this->settings['recursive'],
			'contain' => $this->settings['contain'],
		));
		if ($result) {
			return $result[$Model->alias];
		}

		return false;
	}

/**
 * @see BaseAuthenticate::logout()
 * @param mixed $user
 * @return void
 */
	public function logout($user) {
		FacebookConnect::getInstance()->disconnect(true);
	}

}
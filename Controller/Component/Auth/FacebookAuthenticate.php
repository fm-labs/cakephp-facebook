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
		'fields' => array(
			'facebook_uid' => 'facebook_uid',
		),
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

		// A facebook user is connected, but we won't use a userModel
		// to store/sync user data. So the facebook user info is used
		// as application user data
		if (!$this->settings['userModel']) {
			return $fbUser;
		}

		$userModel = $this->settings['userModel'];
		list(, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];
		$Model = ClassRegistry::init($userModel);

		// Check if we have a user with the current facebook uid in the userModel
		// @todo refactor this part as an event or the model should be responsible for uid lookup
		$conditions = array(
			$model . '.' . $fields['facebook_uid'] => $fbUser['id']
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

		// Obviously, the current user is not stored in the userModel
		// Sync, if possible
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
		//@todo Create a 'syncFacebookUser' fallback
		if (method_exists($Model, 'syncFacebookUser')) {
			return $Model->syncFacebookUser($fbUser, $this->settings);

		} elseif (Configure::read('debug') > 0) {
			throw new Exception(__("Model '%s' has not method 'syncFacebookUser(\$fbUser, \$settings')",
				$userModel));
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
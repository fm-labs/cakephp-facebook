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
        debug("FacebookAuthenticate::authenticate()");
		return $this->getUser($request);
	}

/**
 * @see BaseAuthenticate::getUser()
 */
	public function getUser(CakeRequest $request) {
        debug("FacebookAuthenticate::getUser()");
		$fbUser = FacebookConnect::user();
        debug($fbUser);

		// No facebook user is connected
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

        if (!$fbUser) {
			return false;
		}

		// @todo check default permissions

		// A facebook user is connected, but no user model selected
        // So return facebook user info
		if (!$this->settings['userModel']) {
			return $fbUser;
		}

        return $this->_findFacebookUser($fbUser);

	}

    /**
     * Find user by facebook user info the cakephp way
     *
     * @param $fbUser
     * @return mixed
     */
    protected function _findFacebookUser($fbUser) {
        $Model = ClassRegistry::init($this->settings['userModel']);
        if (!$Model->Behaviors->loaded('FacebookAuthUser')) {
            $config = $this->settings;
            unset($config['userModel']);
            //unset($config['sync']);
            $Model->Behaviors->load('Facebook.FacebookAuthUser', $config);
        }

        $userId = $Model->syncFacebookUser($fbUser);

        debug("AuthUserId For FacebookUid:");
        debug($userId);

        if (!$userId) {
            return false;
        }
        return $this->_findUser(array($Model->alias . '.' . $Model->primaryKey => $userId));
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
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
            'facebook_uid' => 'facebook_uid'
        ),
		'scope' => array(),
		'recursive' => 0,
		'contain' => null,
		'defaultPermissions' => array(),
	);

    public function __construct(ComponentCollection $collection, $settings) {
        parent::__construct($collection, $settings);
    }

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

        // attach FacebookAuthUser behavior
        if (!$Model->Behaviors->loaded('FacebookAuthUser')) {
            $config = array(
                'fields' => $this->settings['fields']
            );
            $Model->Behaviors->load('Facebook.FacebookAuthUser', $config);
        }

        // sync facebook user
        $userId = call_user_func(array($Model, 'syncFacebookUser'), $fbUser);
        if (!$userId) {
            return false;
        }

        // @todo fix BaseAuthenticate::_findUser() to work with custom user model which use an custom alias
        //$user = $this->_findUser(array($Model->alias . '.' . $Model->primaryKey => $userId));

        $conditions = array($Model->alias . '.' . $Model->primaryKey => $userId);
        if (!empty($this->settings['scope'])) {
            $conditions = array_merge($conditions, $this->settings['scope']);
        }
        $result = $Model->find('first', array(
            'conditions' => $conditions,
            'recursive' => $this->settings['recursive'],
            'contain' => $this->settings['contain'],
        ));
        if (empty($result) || empty($result[$Model->alias])) {
            return false;
        }
        $user = $result[$Model->alias];
        /*
        if (
            isset($conditions[$Model->alias . '.' . $fields['password']]) ||
            isset($conditions[$fields['password']])
        ) {
            unset($user[$fields['password']]);
        }
        */
        unset($result[$Model->alias]);
        return array_merge($user, $result);
    }

/**
 * @see BaseAuthenticate::logout()
 * @param mixed $user
 * @return void
 */
	public function logout($user) {
		FacebookConnect::getInstance()->disconnect(false);
	}

}
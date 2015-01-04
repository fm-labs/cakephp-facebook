<?php
App::uses('BaseAuthenticate', 'Controller/Component/Auth');
App::uses('FacebookApi', 'Facebook.Lib');

class FacebookAuthenticate extends BaseAuthenticate {

/**
 * Settings for this object.
 *
 * Common Auth adapter settings:
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The model name of the User, defaults to User.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `array('User.is_active' => 1).`
 * - `recursive` The value of the recursive key passed to find(). Defaults to 0.
 * - `contain` Extra models to contain and store in session.
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
	);

/**
 * @var FacebookApi
 */
    protected $FacebookApi;

    public function __construct(ComponentCollection $collection, $settings) {
        parent::__construct($collection, $settings);

        $this->FacebookApi = FacebookApi::getInstance();
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

        if (!$this->FacebookApi->getSession() && !$this->FacebookApi->connect()) {
            debug("FacebookAuthenticate::getUser() No active session");
            return false;
        }

        $user = $this->FacebookApi->getUser();

		// A facebook user is connected, but no user model selected
		if (!$this->settings['userModel']) {
			return $user;
		}

        return $this->_findFacebookUser($user);
	}

    /**
     * Find user by facebook user info the cakephp way
     *
     * @param $user
     * @return mixed
     */
    protected function _findFacebookUser($user) {
        $Model = ClassRegistry::init($this->settings['userModel']);

        // attach FacebookAuthUser behavior
        if (!$Model->Behaviors->loaded('FacebookAuthUser')) {
            $config = array(
                'fields' => $this->settings['fields']
            );
            $Model->Behaviors->load('Facebook.FacebookAuthUser', $config);
        }

        // sync facebook user
        $modelId = $Model->findFacebookUser($user);
        if (!$modelId) {
            if (Configure::read('debug') > 0) {
                //@TODO Use proper Exception class
                throw new Exception(
                    sprintf("FacebookAuthenticate::getUser() Facebook user with ID '%s' not found in Model '%s'", $user['id'], $Model->alias)
                );
            }
            return false;
        }

        // @todo fix BaseAuthenticate::_findUser() to work with custom user model which uses a custom alias
        //$user = $this->_findUser(array($Model->alias . '.' . $Model->primaryKey => $userId));

        $conditions = array($Model->alias . '.' . $Model->primaryKey => $modelId);
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
		$this->FacebookApi->disconnect();
	}

}
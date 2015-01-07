<?php
App::uses('BaseAuthenticate', 'Controller/Component/Auth');
App::uses('FacebookApi', 'Facebook.Lib');
App::uses('FacebookSyncException', 'Facebook.Lib/Exception');

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
		'scope' => array(),
		'recursive' => 0,
		'contain' => null,
		'fields' => array(
			'facebook_uid' => 'facebook_uid'
		),
		'sync' => false,
	);

/**
 * @var FacebookApi
 */
	public $FacebookApi;

	public function __construct(ComponentCollection $collection, $settings) {
		parent::__construct($collection, $settings);

		$this->FacebookApi = FacebookApi::getInstance();
	}

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
		if (!$this->FacebookApi->getSession() && !$this->FacebookApi->connect()) {
			return false;
		}

		$fbUser = $this->FacebookApi->getUser();

		// A facebook user is connected, but no user model selected
		if (!$this->settings['userModel']) {
			return $fbUser;
		}

		return $this->_findFacebookUser($fbUser);
	}

/**
 * @param $fbUser Facebook user data
 * @return array|bool Model user data
 * @throws FacebookSyncException
 * @throws Exception
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

		// find user
		//@TODO Set Auth error flash message on error
		try {
			$modelId = $Model->findFacebookUser($fbUser);

			// sync facebook user, if enabled
			if (!$modelId && $this->settings['sync'] === true) {
				$modelId = $Model->syncFacebookUser($fbUser);
			}

			if (!$modelId) {
				throw new FacebookSyncException(
					sprintf("Facebook user with ID '%s' not found in Model '%s'", $fbUser['id'], $Model->alias)
				);
			}
		} catch (FacebookSyncException $ex) {
			$this->FacebookApi->log('Sync error: ' . $ex->getMessage(), LOG_ERR);
			throw $ex;

		} catch (Exception $ex) {
			throw $ex;
		}

		// @todo fix BaseAuthenticate::_findUser() to work with custom user model which uses a custom alias
		//$user = $this->_findUser(array($Model->alias . '.' . $Model->primaryKey => $userId));

		// BaseAuthenticate::_findUser() workaround
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
<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 12/15/14
 * Time: 12:36 PM
 */

class FacebookAuthUserBehavior extends ModelBehavior {

    protected $_defaultSettings = array(
        'fields' => array(
            'facebook_uid' => 'facebook_uid'
        ),
        'scope' => array(),
        'recursive' => 0,
        'contain' => null,
    );

    public function setup(Model $Model, $settings = array()) {
        if (!isset($this->settings[$Model->alias])) {
            $this->settings[$Model->alias] = array_merge($this->_defaultSettings, $settings);
        }
    }

    /**
     * Synchronize facebook user info (/me) with the model
     *
     * If no user is found in the model, 'registerFacebookUser' will be triggered.
     * A method with this name should be present in the user model, which should
     * return the user model Id of the just registered facebook user
     *
     * @param Model $Model
     * @param $fbUser
     * @return int User model Id
     */
    public function syncFacebookUser(Model $Model, $fbUser) {
        $settings = $this->settings[$Model->alias];
        $conditions = array(
            $Model->alias . '.' . $settings['fields']['facebook_uid'] => $fbUser['id']
        );

        $user = $Model->find('first', array(
            'fields' => array($Model->primaryKey),
            'conditions' => $conditions,
            'recursive' => -1
        ));

        if (!$user) {
            // No user found
            // Try to call Model::registerFacebookUser()
            // Fallback to FacebookAuthUserBehavior::registerFacebookUser()
            debug("FacebookAuthUser: No user found for facebook user with id " . $fbUser['id']);
            return call_user_func(array($Model, 'registerFacebookUser'), $fbUser);
        }

        return $user[$Model->alias][$Model->primaryKey];
    }

    /**
     *
     * @param Model $Model
     * @param $fbUser Facebook user info (/me)
     * @return int User Id
     */
    public function registerFacebookUser(Model $Model, $fbUser)
    {
        debug("FacebookAuthUser: Register user with facebook uid " . $fbUser['id']);

        $fbUid = $fbUser['id'];
        unset($fbUser['id']);

        // inject facebook uid
        $field = $this->settings[$Model->alias]['fields']['facebook_uid'];
        $fbUser[$field] = $fbUid;
        $fbUser['_facebookAuthUser'] = 'register';

        $Model->create();
        if ($Model->save(array($Model->alias => $fbUser))) {
            return $Model->id;
        }

        return false;
    }


    /**
     * @param Model     $Model
     * @param int       $uid Facebook UID
     * @param array     $params Model find() compatible params
     * @return mixed
     */
    public function findUserByFacebookUid(Model $Model, $uid, $params = array()) {
        $settings = $this->settings[$Model->alias];
        $scope = array(
            $Model->alias . '.' . $settings['fields']['facebook_uid'] => $uid,
        );

        if (isset($params['conditions']) && !empty($params['conditions'])) {
            $params['conditions'] = array_merge($params['conditions'], $scope);
        }
        return $Model->find('first', $params);
    }
} 
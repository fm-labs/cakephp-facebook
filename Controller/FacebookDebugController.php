<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 1/5/15
 * Time: 12:49 PM
 */

class FacebookDebugController extends FacebookAppController {

    public function beforeFilter() {
        parent::beforeFilter();

        if (Configure::read('debug') < 1 || Configure::read('Facebook.enableDebug') !== true) {
            throw new NotFoundException();
        }
    }

    public function index() {

    }
} 
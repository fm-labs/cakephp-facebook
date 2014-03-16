<?php
Router::connect('/facebook/login',
	array('plugin' => 'facebook', 'controller' => 'facebook', 'action' => 'login'));

Router::connect('/facebook/logout',
	array('plugin' => 'facebook', 'controller' => 'facebook', 'action' => 'logout'));

Router::connect('/facebook/connect',
	array('plugin' => 'facebook', 'controller' => 'facebook', 'action' => 'connect'));

Router::connect('/facebook/disconnect',
	array('plugin' => 'facebook', 'controller' => 'facebook', 'action' => 'disconnect'));
<?php
Router::connect('/facebook/login',
	array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'login'));

Router::connect('/facebook/logout',
	array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'logout'));

Router::connect('/facebook/connect',
	array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'connect'));

Router::connect('/facebook/disconnect',
	array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'disconnect'));
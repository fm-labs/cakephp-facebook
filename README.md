cakephp-facebook
================

CakePHP Facebook Plugin V2

This plugin is built on top off the Facebook PHP SDK v5.6 using Facebook API version v2.10


The Facebook SDK for PHP provides developers with a modern, native library
for accessing the Graph API and taking advantage of Facebook Login.
Usually this means you're developing with PHP for a Facebook Canvas app, building your own website,
or adding server-side functionality to an app that already uses the Facebook SDK for JavaScript.


Requirements
------------

- CakePHP 2.3+
- PHP 5.4+


Features
------------

- Authentication
- Social Plugin Helper
- Graph Query


Installation
------------

Via composer

Make sure to set the installer path to your plugins directory right
(usually "plugins/" or "app/Plugin/")

{
    "require": {
        "fm-labs/cakephp-facebook": "dev-master"
    },
    "extra": {
        "installer-paths": {
            "plugins/{$name}/": [
                "fm-labs/cakephp-facebook"
            ]
        }
    }
}
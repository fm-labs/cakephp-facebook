<?php
App::uses('AppHelper', 'View/Helper');
App::uses('FacebookApi', 'Facebook.Lib');

/**
 * @property HtmlHelper $Html
 * @property SessionHelper $Session
 */
class FacebookHelper extends AppHelper {

	const RENDER_TYPE_HTML5 = 0;
	const RENDER_TYPE_XFBML = 1;
	const RENDER_TYPE_IFRAME = 2;

	public $helpers = array('Html', 'Session');

/**
 * Widget renderType
 *
 * @var int
 */
	protected $_renderType = self::RENDER_TYPE_HTML5;

/**
 * Facebook language locale
 *
 * @var string
 */
	protected $_locale = "en_US";

/**
 * Assign facebook js sdk to custom view block
 * before rendering layout
 * @see Helper::beforeLayout()
 */
	public function beforeLayout($layoutFile) {
		$this->_View->assign('facebook-sdk', $this->sdk());
	}

/**
 * Getter / Setter for renderType
 *
 * @param int|null $renderType
 * @return string|FacebookHelper
 */
	public function renderType($renderType = null) {
		if ($renderType === null) {
			return $this->_renderType;
		}

		$this->_renderType = $renderType;
		return $this;
	}

/**
 * Getter / Setter for locale
 *
 * @param string|null $locale
 * @return string|FacebookHelper
 */
	public function locale($locale = null) {
		if ($locale === null) {
			return $this->_locale;
		}

		$this->_locale = $locale;
		return $this;
	}

    public function connectUrl() {
        return array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'connect');
    }

    public function disconnectUrl() {
        return array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'disconnect');
    }

    public function loginUrl() {
        return array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'login');
    }

    public function logoutUrl() {
        return array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'logout');
    }

/**
 * Returns facebook user data
 *
 * @see FacebookConnect::getUser()
 * @param string $key
 * @return mixed
 */
	public function user($key = null) {
		if (!$this->Session->check('Facebook.User')) {
            return null;
        }

        if ($key === null) {
            return $this->Session->read('Facebook.User');
        }

        if ($this->Session->check('Facebook.User.' . (string) $key)) {
            return $this->Session->read('Facebook.User.' . (string) $key);
        }

        return null;
	}

/**
 * Url to profile picture
 *
 * @param string $userId Facebook UserId
 * @return string
 */
	public function userImageUrl($userId = null) {
		if (!$userId) {
			$userId = $this->user('id');
		}

		return "http://graph.facebook.com/" . (string)$userId . "/picture";
	}

/**
 * Html image tag for profile picture
 *
 * @param string $userId Facebook UserId
 * @param array $options Html::image() compatible options
 * @return string
 */
	public function userImage($userId = null, $options = array()) {
		if (is_array($userId)) {
			$options = $userId;
			$userId = null;
		}
		return $this->Html->image($this->userImageUrl($userId), $options);
	}

/**
 * Check user permission(s)
 *
 * @see FacebookApi::validateUserPermission
 * @param string|array $perm
 * @return array|bool
 */
    public function hasPermission($perm) {
        $grantedPerms = (array) $this->Session->read('Facebook.UserPermissions');
        return FacebookApi::validateUserPermission($grantedPerms, $perm);
    }

    public function permissionRequestUrl($perm) {
        if (is_array($perm)) {
            $perm = implode(',', $perm);
        }
        return array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'permission_request', (string) $perm);
    }

    public function permissionRevokeUrl($perm) {
        if (is_array($perm)) {
            $perm = implode(',', $perm);
        }
        return array('plugin' => 'facebook', 'controller' => 'auth', 'action' => 'permission_revoke', (string) $perm);
    }


/**
 * Returns the Facebook JavaScript SDK which should be included
 * right after the opening <body> tag in the layout
 *
 * @return string
 * @TODO Channel URL
 */
	public function sdk() {
		$html = <<<SDK
<div id="fb-root"></div>
<script>
window.fbAsyncInit = function() {
    // init the FB JS SDK
    FB.init({
      appId      : '{{APP_ID}}',
      channelUrl : '{{CHANNEL_URL}}',
      status     : {{STATUS}},
      xfbml      : {{XFBML}},
      cookie     : {{COOKIE}},
    });
  };
</script>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/{{LOCALE}}/all.js";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
SDK;

		$replacements = array(
			'{{APP_ID}}' => (string)Configure::read('Facebook.appId'),
			'{{LOCALE}}' => (string)$this->_locale,
			'{{CHANNEL_URL}}' => '//WWW.YOUR_DOMAIN.COM/channel.html',
			'{{STATUS}}' => 'true',
			'{{XFBML}}' => 'true',
			'{{COOKIE}}' => 'true'
		);
		return str_replace(array_keys($replacements), array_values($replacements), $html);
	}

/***************************************
 ************ Social Plugins ***********
 ***************************************/

/**
 * Login Button
 * The Login Button shows profile pictures of the user's friends
 * who have already signed up for your site in addition to a login button.
 *
 * @see https://developers.facebook.com/docs/reference/plugins/like/
 * @param array $options Widget options
 * @return string
 */
	public function loginButton($options = array()) {
		$options = array_merge(array(
			'show_faces' => false, //specifies whether to display profile photos below the button (standard layout only)
			'width' => 200,
			'max_rows' => 1, // the maximum number of rows of profile pictures to display. Default value: 1.
			'scope' => null,
			'registration_url' => null,
			'size' => null, //  Different sized buttons: small, medium, large, xlarge (default: medium).
		), $options);

		if ($options['registration_url']) {
			$options['registration_url'] = $this->url($options['registration_url'], true);
		}

		return (string)$this->_renderWidget('fb:login-button', $options);
	}

/**
 * Like Button
 * The Like button is a simple plugin that will let people quickly share content with their friends on Facebook.
 *
 * @see https://developers.facebook.com/docs/reference/plugins/like/
 * @param string $url Url to like
 * @param array $options Widget options
 * @return string
 */
	public function likeButton($url = null, $options = array()) {
		$options = array_merge(array(
			'href' => $url,
			'send' => false, // include send button
			'layout' => 'standard', // standard / button_count / box_count
			'colorscheme' => 'light', // the color scheme for the like button. Options: 'light', 'dark'
			'width' => 450,
			'show_faces' => false, //specifies whether to display profile photos below the button (standard layout only)
			'action' => 'like', // the verb to display on the button. Options: 'like', 'recommend'
			'font' => null,
			'ref' => null, // a label for tracking referrals; must be less than 50 characters and can contain alphanumeric characters and some punctuation (currently +/=-.:_)
		), $options);

		$options['href'] = $this->url($options['href'], true);

		return (string)$this->_renderWidget('fb:like', $options);
	}

/**
 * Like Box
 *
 * @see https://developers.facebook.com/docs/reference/plugins/like-box/
 * @param string $url Facebook Page Url
 * @param array $options Widget options
 * @return string
 */
	public function likeBox($url = null, $options = array()) {
		$options = array_merge(array(
			'href' => $url,
			'width' => '300', // the width of the plugin in pixels. Default width: 300px.
			'height' => null, // With the stream displayed, and 10 faces the default height is 556px. With no faces, and no stream the default height is 63px.
			'colorscheme' => 'light', // the color scheme for the like button. Options: 'light', 'dark'
			'show_faces' => true, // specifies whether or not to display profile photos in the plugin. Default value: true.
			'header' => true, // include send button
			'stream' => null, // specifies whether to display a stream of the latest posts from the Page's wall
			'border_color' => null, // the border color of the plugin.
			'force_wall' => null, // for Places, specifies whether the stream contains posts from the Place's wall or just checkins from friends. Default value: false.
		), $options);

		$options['href'] = $this->url($options['href'], true);

		return (string)$this->_renderWidget('fb:like-box', $options);
	}

/**
 * Send Button
 * The Send Button allows users to easily send content to their friends.
 * People will have the option to send your URL in a message to their Facebook friends,
 * to the group wall of one of their Facebook groups, and as an email to any email address
 *
 * @see https://developers.facebook.com/docs/reference/plugins/send/
 * @param array|string $url
 * @param array $options
 * @return string
 */
	public function sendButton($url = null, $options = array()) {
		$options = array_merge(array(
			'href' => $url,
			'colorscheme' => 'light', // the color scheme for the like button. Options: 'light', 'dark'
			'font' => null,
			'ref' => null, // a label for tracking referrals; must be less than 50 characters and can contain alphanumeric characters and some punctuation (currently +/=-.:_)
		), $options);

		$options['href'] = $this->url($options['href'], true);

		return (string)$this->_renderWidget('fb:send', $options);
	}

/**
 * Follow Button
 * The Follow button lets a user follow your public updates on Facebook.
 *
 * @see https://developers.facebook.com/docs/reference/plugins/follow/
 * @param string $url Profile URL
 * @param array $options Widget options
 * @return string
 */
	public function followButton($url = null, $options = array()) {
		$options = array_merge(array(
			'href' => $url,
			'layout' => 'standard', // standard / button_count / box_count
			'show_faces' => false, //specifies whether to display profile photos below the button (standard layout only)
			'colorscheme' => 'light', // the color scheme for the like button. Options: 'light', 'dark'
			'width' => 450,
			'font' => null,
		), $options);

		$options['href'] = $this->url($options['href'], true);

		return (string)$this->_renderWidget('fb:follow', $options);
	}

/**
 * Comments
 * Comments Box is a social plugin that enables user commenting on your site.
 * Features include moderation tools and distribution.
 *
 * @see https://developers.facebook.com/docs/reference/plugins/comments/
 * @param string $url Profile URL
 * @param array $options Widget options
 * @return string
 */
	public function comments($url = null, $options = array()) {
		$options = array_merge(array(
			'href' => $url,
			'width' => 470,
			'colorscheme' => 'light', // the color scheme for the like button. Options: 'light', 'dark'
			'num_posts' => '10', // the number of comments to show by default. Default: 10. Minimum: 1
			'order_by' => 'social', // the order to use when displaying comments. Options: 'social', 'reverse_time', 'time'. Default: 'social'
			'mobile' => null // whether to show the mobile-optimized version. Default: auto-detect.
		), $options);

		$options['href'] = $this->url($options['href'], true);

		return (string)$this->_renderWidget('fb:comments', $options);
	}

/**
 * Activity Feed
 * The Activity Feed plugin displays the most interesting recent activity taking place on your site.
 *
 * @see https://developers.facebook.com/docs/reference/plugins/activity/
 * @param string $site Domain URL
 * @param array $options Widget options
 * @return string
 */
	public function activityFeed($site = null, $options = array()) {
		$options = array_merge(array(
			'site' => null, //  the domain for which to show activity
			'action' => null, // a comma separated list of actions to show activities for.
			'app_id' => null, // will display all actions, custom and built-in, associated with this app_id.
			'width' => 300,
			'height' => 300,
			'header' => true, // specifies whether to show the Facebook header.
			'colorscheme' => 'light', // the color scheme for the like button. Options: 'light', 'dark'
			'font' => null,
			'recommendations' => false, // specifies whether to always show recommendations in the plugin
			'filter' => null, // allows you to filter which URLs are shown in the plugin
			'linktarget' => '_blank', // This specifies the context in which content links are opened. Options: '_top' / '_blank' / '_parent'
			'ref' => null,
			'max_age' => null // the valid values are 1-180, which specifies the number of days. Default: 0
		), $options);

		$options['site'] = $this->url($options['site'], true);

		return (string)$this->_renderWidget('fb:activity', $options);
	}

/**
 * Recommendations Box
 * The Recommendations Box shows personalized recommendations to your users.
 *
 * @see https://developers.facebook.com/docs/reference/plugins/recommendations/
 * @param string $site Domain URL
 * @param array $options Widget options
 * @return string
 */
	public function recommendationsBox($site = null, $options = array()) {
		$options = array_merge(array(
			'site' => null, //  the domain for which to show activity
			'action' => null, // a comma separated list of actions to show activities for.
			'app_id' => null, // will display all actions, custom and built-in, associated with this app_id.
			'width' => 300,
			'height' => 300,
			'header' => true, // specifies whether to show the Facebook header.
			'colorscheme' => 'light', // the color scheme for the like button. Options: 'light', 'dark'
			'font' => null,
			'linktarget' => '_blank', // This specifies the context in which content links are opened. Options: '_top' / '_blank' / '_parent'
			'ref' => null,
			'max_age' => null // the valid values are 1-180, which specifies the number of days. Default: 0
		), $options);

		$options['site'] = $this->url($options['site'], true);

		return (string)$this->_renderWidget('fb:recommendations', $options);
	}

/**
 * Recommendations Bar
 * The Recommendations Bar allows users to like content, get recommendations,
 * and share what they’re reading with their friends.
 *
 * @see https://developers.facebook.com/docs/reference/plugins/recommendationsbar/
 * @param string $url Url of article
 * @param array $options Widget options
 * @return string
 */
	public function recommendationsBar($url = null, $options = array()) {
		$options = array_merge(array(
			'href' => $url, //  the domain for which to show activity
			'trigger' => null, // when the plugin expands
			'read_time' => null, // The number of seconds before the plugin will expand. Default is 30 seconds. Minimum is 10 seconds.
			'action' => null, // The verb to display on the button. Options: 'like', 'recommend'
			'side' => null, //  the side of the screen where the plugin will be displayed. Options: 'left', 'right'. Default: auto
			'site' => null, // a comma separated list of domains to show recommendations for. The default is the domain of the href parameter.
			'ref' => null,
			'num_recommendations' => null, // the number of recommendations to display. By default, this value is 2 and the maximum value is 5.
			'max_age' => null // the valid values are 1-180, which specifies the number of days. Default: 0
		), $options);

		$options['href'] = $this->url($options['href'], true);

		return (string)$this->_renderWidget('fb:recommendations-bar', $options);
	}

/**
 * Render widget options to HTML
 *
 * @param string $type Facebook tag name
 * @param string $options Widget options
 * @return string|boolean
 */
	protected function _renderWidget($type, $options) {
		switch($this->_renderType) {

			case self::RENDER_TYPE_XFBML:
				$attr = $this->_parseAttributesXfbml($options);
				return $this->Html->useTag($type, $attr);

			case self::RENDER_TYPE_HTML5:
				$attr = $this->_parseAttributesHtml5($options);
				$class = Inflector::slug($type, '-');
				return $this->Html->div($class, '', $attr);

			case self::RENDER_TYPE_IFRAME:
			default:
                if (Configure::read('debug') > 0) {
                    throw new Exception(sprintf("FacebookHelper: Unsupported widget render type '%s'", $this->_renderType));
                }
		}

		return false;
	}

/**
 * @param $options
 * @return array
 */
	protected function _parseAttributesHtml5($options) {
		$_options = array();
		foreach ((array)$options as $key => $val) {

			if ($val === null) {
				continue;
			}

			$_key = "data-" . Inflector::slug($key, '-');
			$_val = $val;
			if (is_bool($_val)) {
				$_val = ($_val === true) ? 'true' : 'false';
			}
			$_options[$_key] = $_val;
		}

		return $_options;
	}

/**
 * @param $options
 * @return array
 */
	protected function _parseAttributesXfbml($options) {
		$_options = array();
		foreach ((array)$options as $key => $val) {

			if ($val === null) {
				continue;
			}

			$_key = Inflector::slug($key, '-');
			$_val = $val;
			if (is_bool($_val)) {
				$_val = ($_val === true) ? 'true' : 'false';
			}
			$_options[$_key] = $_val;
		}

		return $_options;
	}
}
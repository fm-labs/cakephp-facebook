<?php
/**
 * Facebook SDK async init script - example
 *
 * Customize this init script by creating your own view element
 * in your view or theme folder. See example script.
 *
 * e.g.
 * app/View/Plugin/Facebook/Elements/facebook/sdk_init_script.ctp
 *
 * @param $fbInit 	Rendered window.fbAsyncInit options
 * 					This MUST be included
 */
?>
<script>
	// https://developers.facebook.com/docs/facebook-login/login-flow-for-web/v2.2

	// This is called with the results from from FB.getLoginStatus().
	function statusChangeCallback(response) {
		console.log('statusChangeCallback');
		console.log(response);
		// The response object is returned with a status field that lets the
		// app know the current login status of the person.
		// Full docs on the response object can be found in the documentation
		// for FB.getLoginStatus().
		if (response.status === 'connected') {
			// Logged into your app and Facebook.
			console.log("Connected with Facebook");

			FB.api('/me', function(response) {
				console.log('Successful login for: ' + response.name);
				//document.getElementById('status').innerHTML =
				//	'Thanks for logging in, ' + response.name + '!';
			});

		} else if (response.status === 'not_authorized') {
			// The person is logged into Facebook, but not your app.
			console.log('You are not authorized');
		} else {
			// The person is not logged into Facebook, so we're not sure if
			// they are logged into this app or not.
			console.log("Please log into Facebook.")
		}
	}

	// This function is called when someone finishes with the Login
	// Button.  See the onlogin handler attached to it in the sample
	// code below.
	function checkLoginState() {
		FB.getLoginStatus(function(response) {
			statusChangeCallback(response);
		});
	}

	window.fbAsyncInit = function() {

		<?php echo $fbInit; ?>

		// fired when the user sends a message using the send button.
		// The response object passed into the callback function contains the URL which was sent
		FB.Event.subscribe('message.send', function(response) {
			console.log('[message.send] You sent a message to the URL: ' + response);
		});

		// fired when the user likes something (fb:like).
		// The response parameter to the callback function contains the URL that was liked
		FB.Event.subscribe('edge.create', function(response) {
			console.log('[edge.create] You liked the URL: ' + response);
		});

		// fired when the user unlikes something (fb:like).
		// The response parameter to the callback function contains the URL that was unliked
		FB.Event.subscribe('edge.remove', function(response) {
			console.log('[edge.remove] You unliked the URL: ' + response);
		});

		// fired when the user adds a comment (fb:comments).
		// The response object passed into the callback function looks like
		// {
		//  href: "",         /* Open Graph URL of the Comment Plugin */
		//  commentID: "",    /* The commentID of the new comment */
		// }
		FB.Event.subscribe('comment.create', function(response) {
			console.log('[comment.create] You commented the URL: ' + response);
		});

		// fired when the user removes a comment (fb:comments).
		// The response object passed into the callback function looks like
		// {
		//  href: "",         /* Open Graph URL of the Comment Plugin */
		//  commentID: "",    /* The commentID of the new comment */
		// }
		FB.Event.subscribe('comment.remove', function(response) {
			console.log('[comment.remove] You removed comment for the URL: ' + response);
		});

		// fired when user is prompted to log in or opt in to Platform after clicking a Like button.
		// The response parameter to the callback function contains the URL that initiated the prompt
		FB.Event.subscribe('auth.prompt', function(response) {
			console.log('[edge.create] You liked the URL: ' + response);
		});

		// fired when the authResponse changes
		FB.Event.subscribe('auth.authResponseChange', function(response) {
			console.log('[auth.authResponseChange] The status of the session is: ' + response.status);
			console.log(response);
		});

		// fired when the auth status changes from unknown to connected
		FB.Event.subscribe('auth.login', function(response) {
			console.log('[auth.login] The status of the session is: ' + response.status);
			console.log(response);

			checkLoginState();
		});

		// fired when the status changes
		// (see FB.getLoginStatus for additional information on what this means)
		FB.Event.subscribe('auth.statusChange', function(response) {
			console.log('[auth.statusChange] The status of the session is: ' + response.status);
			console.log(response);
		});

		// fired when the user logs out. The response object passed into the callback function looks like
		// { status: "",         /* Current status of the session */ }
		FB.Event.subscribe('auth.logout', function(response) {
			console.log('[auth.logout] The status of the session is: ' + response.status);
			console.log(response);
		});

		//FB.getLoginStatus(function(response) {
		//	statusChangeCallback(response);
		//});

	}
</script>
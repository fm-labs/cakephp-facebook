<?php
/**
 * Facebook SDK async init script
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
	window.fbAsyncInit = function() {
		<?php echo $fbInit; ?>
	}
</script>
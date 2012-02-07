<?php
require_once(dirname(__FILE__) . '/../Overlay.php');

// Lightbox Overlay Extension
class LightboxOverlay extends Overlay {
	
	var $name = 'Lightbox';
	
	function LightboxOverlay($suppress = false) {
		if(!$suppress) {
			add_action('init', array(&$this, 'LoadScripts'));
		}
	}
	
	function LoadScripts()
	{
		if(!is_admin()) {
			
			// Enqueue Javascript
			wp_enqueue_script('jquery-lightbox',plugins_url('/js/jquery.lightbox-0.5.min.js', dirname(__FILE__)), array('jquery'), '0.5');
			wp_enqueue_script('wfm-lightbox',plugins_url('/js/wfm-lightbox.js', dirname(__FILE__)), array('jquery-lightbox'), '20110428');
			
			// Enqueue CSS
			wp_enqueue_style('wfm-lightbox-css', plugins_url('/css/jquery.lightbox-0.5.css', dirname(__FILE__)));
			
		}
	}
	
}
?>
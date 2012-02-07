<?php
require_once(dirname(__FILE__) . '/../Overlay.php');

//Galleria extension
class GalleriaOverlay extends Overlay
{
	
	var $name = 'Galleria';
	
	function GalleriaOverlay($suppress = false) {
		if(!$suppress) {
			add_action('init', array(&$this, 'LoadJavascript'));
		}
	}
	
	function LoadJavascript()
	{
		if(!is_admin()) {
			
			wp_enqueue_script('jquery-galleria',plugins_url('/js/galleria-1.2.2.min.js', dirname(__FILE__)), array('jquery'));
			wp_enqueue_script('wfm-galleria',plugins_url('/js/wfm-galleria.js', dirname(__FILE__)), array('jquery'));
		
		}
	}
	
}
?>
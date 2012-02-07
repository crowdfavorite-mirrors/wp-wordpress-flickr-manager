<?php
require_once(dirname(__FILE__) . '/../Overlay.php');

// Wordpress Overlay Extension
class WordpressOverlay extends Overlay 
{
	
	var $name = 'Wordpress';
	var $photo = null;
	
	function WordpressOverlay($suppress = false) {
		if(!$suppress) {
			add_action('wp_print_scripts', array(&$this, 'LoadJavascript'));
			add_filter('query_vars', array(&$this, 'RegisterQueryVars') );
			add_action('parse_request',array(&$this, 'RegisterPageHook'));
		}
	}
	
	function RegisterPageHook($wp) {
		if(preg_match ('/flickr\/(\d+)\/?$/', $wp->request, $matches)) {
			$wp->query_vars['flickrid'] = $matches[1];
		}
		
		if(!empty($wp->query_vars['flickrid'])) {
			status_header(200);
			remove_action('template_redirect', 'redirect_canonical');
			add_action('template_redirect', array(&$this, 'RenderPhotoPage'));
		}
	}
	
	
	function RenderPhotoPage() {
		global $wp, $flickr_manager;
		
		$photoid = $wp->query_vars['flickrid'];
		
		
		$photo = $flickr_manager->flickr->photos_getInfo($photoid);
		$photo = $photo['photo'];
		$this->photo = $photo;
		
		add_filter('wp_title', array(&$this, 'RenderPageTitle'),10,2);
		
		
		if (file_exists(TEMPLATEPATH  . '/FlickrPage.php')) {
			require_once(TEMPLATEPATH . '/FlickrPage.php');
		} else {
			require_once(dirname(__FILE__) . '/../templates/FlickrPage.php');
		}
		
		exit();
	}
	
	
	function RenderPageTitle($title, $sep) {
		return ' ' . ((!empty($this->photo['title'])) ? $this->photo['title'] : $title) . ' ';
	}
	
	
	function RegisterQueryVars( $qvars )
	{
	  $qvars[] = 'flickrid';
	  return $qvars;
	}
	
	function LoadJavascript()
	{
		global $wp_rewrite;
    
		if(!is_admin()) {
			
			echo sprintf("<script type='text/javascript'>\n//<![CDATA[\nvar WFM_Permalinks = %s;\n//]]>\n</script>"
						,(isset($wp_rewrite) && $wp_rewrite->using_permalinks()) ? 'true' : 'false');
			
			// Load Javascript
			wp_enqueue_script('wfm-wpoverlay',plugins_url('/js/wfm-wpoverlay.js', dirname(__FILE__)), array(), '20110428');
			
		}
	}
}
?>
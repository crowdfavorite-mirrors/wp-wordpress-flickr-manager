<?php
// Load Dependencies
require_once(dirname(__FILE__) . '/../Overlay.php');

// Highslide Overlay Extension
class HighslideOverlay extends Overlay {
	
	var $name = 'Highslide';
	
	function HighslideOverlay($suppress = false) {
		if(!$suppress) {
			add_action('init', array(&$this, 'LoadScripts'));
			add_action('wp_head', array(&$this, 'PrintCSS'));
		}
	}
	
	function LoadScripts()
	{
		if(!is_admin()) {
			
			wp_enqueue_script('highslide',plugins_url('/js/highslide-full.min.js', dirname(__FILE__)));
			wp_enqueue_script('wfm-highslide',plugins_url('/js/wfm-highslide.js', dirname(__FILE__)), array('jquery','swfobject','highslide'), '20110428');
			
			wp_enqueue_style('wfm-highslide-css', plugins_url('/css/highslide.css', dirname(__FILE__)));
			
		}
	}
	
	function PrintCSS()
	{
		if(!is_admin()) {
			echo sprintf("<!--[if IE 6]>\n<link rel='stylesheet' href='%s' type='text/css' />\n<![endif]-->", plugins_url('/css/highslide-ie6.css', dirname(__FILE__)));
			
			echo "<script type='text/javascript'>\n//<![CDATA\n";
			echo sprintf("var WFM_iFrameSRC = '%s';\n", plugins_url('/' . basename(dirname(__FILE__)) . '/' . basename(__FILE__), dirname(__FILE__)));
			echo "//]]>\n</script>";
		}
	}
	
}

if(strstr(basename($_SERVER['REQUEST_URI']), basename(__FILE__))) :

	// AJAX Handler for HTML 5 overlay
	error_reporting(E_ERROR);
	
	// Load Wordpress Core
	require_once( dirname(__FILE__) . '/../../../../wp-load.php');
	$wp->init();
	
	global $flickr_manager;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title></title>
</head>

<body style="margin: 0; padding: 0;">
<div style="width: 640px; margin: 0 auto;">
<?php echo $flickr_manager->RenderVideo($_GET['vid'], 'html5'); ?>
</div>
</body>
</html>	

<?php
endif;
?>
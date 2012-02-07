<?php
// Load Dependencies
require_once(dirname(__FILE__) . '/lib/inc.templater.php');

// Overlay extension loader
class OverlayLoader {
	
	var $overlayDir;
	var $plugins = array();
	var $overlay;
	
	function __construct($overlayDir) 
	{
		$this->overlayDir = dirname(__FILE__) . "/" . $overlayDir;
		
		$this->LoadPlugins();
		
		add_action('plugins_loaded', array(&$this, 'Initialize'));
	} 
	
	function Initialize() {
		global $flickr_manager;
		
		if(!empty($flickr_manager->settings['image_viewer'])) {
			
			if($this->LoadOverlay($flickr_manager->settings['image_viewer'])) {
				add_action('init', array(&$this, 'LoadCommonJavascript'));
				add_action('wp_head', array(&$this, 'LoadWFMVariables'));
			}
			
		}
	}
	
	function LoadPlugins() 
	{
		if($handle = opendir($this->overlayDir)) {
			
			while(($file = readdir($handle)) !== false) {
				if(!is_dir(realpath($this->overlayDir . '/' . $file)) && substr($file,0,1) != "." && $name = $this->GetName($file)) {
					$this->plugins[strtolower($name)] = $file;
				}
			}
			
		}
	}
	
	function GetName($plugin) 
	{
		$includePath = $this->overlayDir . '/' . $plugin;
		if(!file_exists($includePath) || substr($includePath, -4) != '.php') 
			return;
		
		include_once($includePath);
		
		$class = substr($plugin,0,strpos($plugin,".",0));
		
		if(!class_exists($class))
			return;
		
		$instance = new $class(true);
		return $instance->GetName();
	}
	
	function GetOverlays() 
	{
		return array_keys($this->plugins);
	}
	
	function LoadOverlay($overlay)
	{
		$plugin = $this->plugins[$overlay];
		
		$includePath = $this->overlayDir . '/' . $plugin;
		if(empty($plugin) || !file_exists($includePath)) 
			return false;
			
		include_once($includePath);
		
		$class = substr($plugin,0,strpos($plugin,".",0));
		$this->overlay = new $class();
		
		return true;
	}
	
	function LoadCommonJavascript() {
		wp_enqueue_script('wfm-common',plugins_url('/js/wfm-common.js', __FILE__), array('jquery'),'20110429');
	}
	
	function LoadWFMVariables() {
		global $flickr_manager;
		
		$settings = array(
			'WFM_PluginDir' => $flickr_manager->absoluteURL
			,'WFM_ViewOnFlickr' => __('View on Flickr', 'flickr-manager')
			,'WFM_CaptionLink' => $flickr_manager->settings['flickr_link']
		);
		
		echo "<script type='text/javascript'>\n//<![CDATA[\n";
		
		foreach($settings as $k => $v) {
			echo sprintf("var %s = '%s';\n", $k, $v);
		}
		
		echo "//]]>\n</script>";
	}
	
	function GetActiveOverlay() 
	{
		if(!empty($this->overlay)) {
			return $this->overlay->GetName();
		} else {
			return null;
		}
	}
}
?>
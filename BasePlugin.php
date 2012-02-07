<?php
// Plugin base

class BasePlugin 
{

	var $settings;
	var $plugin_option = 'wfm-settings';
	var $plugin_domain = 'flickr-manager';
	var $plugin_directory;
	var $absoluteURL;
	
	function BasePlugin() 
	{
		$this->plugin_directory = dirname(plugin_basename(__FILE__));
		$this->absoluteURL = plugins_url('/', __FILE__);
		
		// Register the installation handler
		register_activation_hook( __FILE__, array(&$this, 'InstallPlugin') );
	
		$this->LoadSettings();
		
		// Load locale settings
		$lang_dir = sprintf("%s/%s/lang", PLUGINDIR, dirname(plugin_basename(__FILE__)));
		load_plugin_textdomain($this->plugin_domain, $lang_dir);
	}
	
	function InstallPlugin() {
	
		if (!get_option($this->plugin_option)) {
			$this->settings = array();
			add_option($this->plugin_option, $this->settings);
		}
		
	}
	
	function LoadSettings() 
	{
		
		$this->settings = get_option($this->plugin_option);
		
	}
	
	function GetSetting($setting) 
	{
	
		return $this->settings[$setting];
		
	}
	
	function SaveSetting($setting, $value)
	{
		
		$this->settings[$setting] = $value;
		update_option($this->plugin_option, $this->settings);
		
	}
	
}
?>
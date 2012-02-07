<?php

class Page
{
	var $page;
	
	function Page($template) {
		$plugin_dir = realpath(dirname(__FILE__) . '/../');
		$template =  "$plugin_dir/$template";
	
		if (file_exists($template)) {
	    	$this->page = join("", file($template));
		} else {
	    	die("Template file $template not found.");
		}
	}
	
	function replace_tags($tags) {
		if (sizeof($tags) > 0) {
		    foreach ($tags as $tag => $data) {
		    	$this->page = str_replace("[" . $tag . "]", $data,$this->page);
			}
	    }
	}
	
	function render($params = array()) {
		$this->replace_tags($params);
	
		return $this->page;
	}
}

?>
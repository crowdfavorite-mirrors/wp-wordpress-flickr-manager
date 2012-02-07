<?php
// Overlay Interface
class Overlay {
	
	var $name;
	
	function Overlay($name) {
		$this->name = $name;
	}
	
	function GetName() 
	{
		return $this->name;
	}
	
}
?>
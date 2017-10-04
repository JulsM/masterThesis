<?php 
Autoloader::register();
class Autoloader {
	
	public function __construct() {
      spl_autoload_register(array($this, 'load_class'));
      spl_autoload_register(array($this, 'load_file'));
    }
    
    public static function register(){
      new Autoloader();
    }
    
    public function load_class($class_name) {
	    $file = 'classes/'.$class_name.'.php';
	    if(file_exists($file)) {
	        require_once($file);
	    }
    }

    public function load_file($class_name) {
	    $file = $class_name.'.php';
	    if(file_exists($file)) {
	        require_once($file);
	    }
    }
}
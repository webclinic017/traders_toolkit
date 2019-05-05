<?php 
class CustomException extends Exception {

	/* 
	/* Data Members
	/*							*/
	
	//private $msg;
	
	/* 
	/* Methods
	/*							*/
	
	public function __construct($message) { //parent method accepts __construct($message, $code) 

		parent::__construct($message);
		
		$this->message = sprintf('<p>File <em>%s</em> line: <em>%s</em> <br /> %s</p>', $this->getFile(), $this->getLine(), $message);
		
		error_log($this->message);
		
	}
	
	
	public function __toString() {
		return $this->message;
	
	}
	

}
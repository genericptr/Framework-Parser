<?php

/**
* Singleton class for managing error reporting
*
* 	Usage:
* 	ErrorReporting::errors()->add_warning("some warning");
*
*/
class ErrorReporting {
	
	private static $instance;
	
	private $enable_messages = true;
	private $enable_notes = true;
	private $enable_warnings = true;
	private $enable_errors = true;
	private $enable_fatals = true;
	private $enable_exceptions = true;
	
	/**
	 * Class Methods
	 */
	
	// returns the global error reporting instance
	public static function errors () {
		if (!isset(self::$instance)) {
			$class_name = __CLASS__;
	    self::$instance = new $class_name;
		}
    
		return self::$instance;
	}
	
	/**
	 * Parser Error Reporting
	 * Used in parser output but doesn't affect the program
	 */
	
	// messages are plain text inserted to the console with no suggestions
	public function add_message ($message) {
		if (!$this->enable_messages) return;
		
		print($message."\n");
	}

	// notes are reminders of information which may not be important
	// but users would like to know
	public function add_note ($message) {
		if (!$this->enable_notes) return;
		
		print("Note: ".$message."\n");
	}

	// warnings are potentially problematic issues that are not severe enough to stop the parser
	public function add_warning ($message) {
		if (!$this->enable_warnings) return;
		
		print("* Warning: ".$message."\n");
	}
	
	// errors are will likely causing parsing to fail but not not severe enough to stop the parser
	public function add_error ($message) {
		if (!$this->enable_errors) return;
		
		print("* Error: ".$message."\n");
	}
	
	// fatal errors are guaranteed to crash or make the parser fail
	// after adding an error of this level the parser will stop 
	public function add_fatal ($message) {
		if (!$this->enable_fatals) return;
		//debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		
		die("Fatal: ".$message."\n");
	}
	
	/**
	 * Debugging
	 */
	
	// adds a plain exception in development code (used for debugging only, replace with add_fatal for production)
	public function add_exception ($message) {
		if (!$this->enable_exceptions) return;
				
		throw new Exception($message);
	}
	
	private function __construct() {		
	}

}

?>
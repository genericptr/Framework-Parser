<?php

require_once("symbol_table.php");
require_once("header.php");
require_once("errors.php");
require_once("language_utilities.php");

/**
* 
*/
class HeaderParserModule extends MemoryManager {
	public $identifier;						// unique identifier used for reference modules in patterns
	public $name;									// name for debugging

	protected $header;						// reference to the current header being parsed
	protected $symbols;						// reference to the global symbol table
	
	private $patterns = array();
		
	/**
	 * Accessors
	 */
	
	public function get_patterns () {
		return $this->patterns;
	}
		
	/**
	 * Patterns
	 */
	
	public function add_pattern (array $pattern) {
		
		// attempt to replace $1 placeholders with in the
		// pattern which specified dynamic values
		for ($i=1; $i < 10; $i++) { 
			if ($pattern["\$$i"]) {
				if ($pattern[PATTERN_KEY_PATTERN]) $pattern[PATTERN_KEY_PATTERN] = str_replace("\$$i", $pattern["\$$i"], $pattern[PATTERN_KEY_PATTERN]);
				if ($pattern[PATTERN_KEY_START]) $pattern[PATTERN_KEY_START] = str_replace("\$$i", $pattern["\$$i"], $pattern[PATTERN_KEY_START]);
				if ($pattern[PATTERN_KEY_END]) $pattern[PATTERN_KEY_END] = str_replace("\$$i", $pattern["\$$i"], $pattern[PATTERN_KEY_END]);
			} else {
				break;
			}
		}
		
		// add to array with extra private keys
		$this->patterns[] = array(	PATTERN_KEY_PATTERN => $pattern[PATTERN_KEY_PATTERN],
																PATTERN_KEY_START => $pattern[PATTERN_KEY_START],
																PATTERN_KEY_END => $pattern[PATTERN_KEY_END],
																PATTERN_KEY_BREAK => $pattern[PATTERN_KEY_BREAK],
																PATTERN_KEY_ID => $pattern[PATTERN_KEY_ID],
																PATTERN_KEY_SCOPE => $pattern[PATTERN_KEY_SCOPE],
																PATTERN_KEY_MODULES => $pattern[PATTERN_KEY_MODULES],
																PATTERN_KEY_LOCATION_OFFSET => $pattern[PATTERN_KEY_LOCATION_OFFSET],
																PATTERN_KEY_TERMINATE_FROM_START => $pattern[PATTERN_KEY_TERMINATE_FROM_START],
																
																// private keys
																PATTERN_KEY_MODULE => &$this,
																PATTERN_KEY_IDENTIFIER => uniqid(),
																);
	}
			
	/**
	 * Control methods
	 */
	
	// override to process the results from a terminated scope.
	// if a symbol was parsed from the scope results return the symbol
	// to the scope using Scope::set_symbol.
	//
	// $id = id of the pattern that was matched
	// $scope = the scope which the pattern was called from
	//					you can retreive pattern results using $scope->start_results or $scope->results
	public function process_scope ($id, Scope $scope) {
		if (get_verbosity() > 1) {
			print("Found $this->name (pattern #$id) at $scope->start/$scope->end\n");
			print("$scope->contents\n");
			if ($scope->results) {
				print_r($scope->results);
			}
			if ($scope->start_results) {
				print_r($scope->start_results);
			}
		}
	}
	
	// override to accept the results of the pattern
	// if you choose to reject the results the offset will be skipped for the pattern
	// and yielded to the next pattern (if any) that will accept the next offset.
	// this is the method of last resort if the modules pattern is consuming something is shouldn't
	// and breaking the balance of start/end ranges for example
	//
	// $id = id of the pattern that was matched
	// $results = array of matches for the pattern
	public function accept_pattern_results ($id, $results) {
		return true;
	}
	
	// override the reject the module from pattern matching in the scope
	// this method serves the same purpose as accept_pattern_results except
	// it will prevent the parser from ever trying to match any patterns
	// if the scope is not selected.
	//
	// you can use Scope::is_within to determine if the scope is within a range
	// the module can accept.
	public function accept_scope (Scope $scope) {
		return true;
	}
	
	// override to prepare the header contents for parsing
	public function prepare (&$contents) {
	}
	
	// override to conclude the parsing
	public function conclude () {	
	}
	
	// override to initialize
	public function init () {
	}
		
	protected function __free() {
		parent::__free();
		
		unset($this->header);
		unset($this->patterns);
		//MemoryManager::free_array($this->patterns);
	}
			
	function __construct($identifier, Header $header) {	
		if (!$header->exists()) {
			ErrorReporting::errors()->add_fatal("The header ".$header->get_name()." can't be found.");
		}
			
		$this->identifier = $identifier;
		$this->header = &$header;
		$this->symbols = &SymbolTable::table();

		$this->init();
	}
}
		

?>
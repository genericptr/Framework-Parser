<?php

require_once("memory_manager.php");

/**
 * Scopes
 */

class Scope extends MemoryManager {
	public $name;											// name of the scope (defined as SCOPE__XXXX)
	public $start = -1;
	public $end = -1;
	public $contents = null;
	public $symbol = null;
	public $uuid;
	
	public $results = null;						// all captures for single pattern scopes
	public $start_results = null;			// start captures for range pattern scopes
	public $end_results = null;				// end captures for range pattern scopes
	public $pattern = null;						// pattern which captured the scope
	public $next_end_result = null;		// when start ranges are opened this is the next available end result
																		// from the starting offset
	public $recursion;
	public $start_from;
	public $failed_patterns = array();
	
	protected $header;
	
	private $sub_scopes = array();
	private $super_scope = null;
	
	private static $scope_classes = array();
	
	/**
	 * Class Methods
	 */
	
	public static function add_class_for_scope ($scope, $class) {
		self::$scope_classes[$scope] = $class;
	}
	
	public static function find_class_for_scope ($scope) {
		
		if (!$class_name = self::$scope_classes[$scope]) {
			$class_name = __CLASS__;
		}
		
		return $class_name;
	}
	
	/**
	 * Accessors
	 */
	
	public function set_symbol ($symbol) {
		if ($symbol) {
			$this->symbol = &$symbol;
			$this->symbol->set_scope($this);
		} else {
			$this->symbol = null;
		}
	}
	
	public function get_super_scope () {
		return $this->super_scope;
	}
	
	public function get_sub_scopes () {
		return $this->sub_scopes;
	}
	
	public function set_super_scope (Scope $scope) {
		$this->super_scope = &$scope;
	}
	
	public function add_sub_scope (Scope &$scope) {
		$scope->set_super_scope($this);
		$scope->added_to_scope($this);
		$this->sub_scopes[] = &$scope;
	}
	
	public function is_open () {
		return (($this->start >= 0) && ($this->end > 0));
	}
	
	public function is_super ($scope) {
		return ($this->super_scope->name == $scope);
	}
	
	/**
	 * Methods
	 */
		
	// returns true if the scope conforms to any value of $scope (single or array of scope names)
	public function conforms_to ($scope) {
		if (!is_array($scope)) {
			$scopes = array($scope);
		} else {
			$scopes = $scope;
		}
				
		return in_array($this->name, $scopes);
	}
	
	public function is_within ($scope) {
		if ($this->super_scope) return $this->super_scope->find_super_scope($scope, false);
	}
	
	// returns the super scope of the specified name by
	// looking up the scope chain start with the current scope
	//
	// $scope = a single scope name or an array of scope names
	// $return_symbol = if true returns the symbol associate with the scope
	public function find_super_scope ($scope, $return_symbol = false) {
		
		if (!is_array($scope)) {
			$scopes = array($scope);
		} else {
			$scopes = $scope;
		}
		
		if (in_array($this->name, $scopes)) {
			if ($return_symbol) {
				return $this->symbol;
			} else {
				return $this;
			}
		} elseif ($this->super_scope) {
			return $this->super_scope->find_super_scope($scope, $return_symbol);
		}
	}
	
	// returns a flat array of super scopes from the current scope up to the root
	public function find_super_scopes_flat ($return_symbol = false) {
		$scopes = array();
		
		if ($this->super_scope) {
			if ($return_symbols) {
				if ($this->super_scope->symbol) $scopes[] = &$this->super_scope->symbol;
			} else {
				$scopes[] = &$this->super_scope;
			}
			$scopes = array_merge($scopes, $this->super_scope->find_super_scopes_flat($return_symbols));
		}
		
		return $scopes;
	}
	
	// returns an array of all sub-scopes matching $scope
	// 
	// $scope = a single scope name or an array of scope names
	// $return_symbols = if true returns the symbol associate with the scope
	public function find_sub_scopes ($scope, $return_symbols = false, $recurse = true) {
		$scopes = array();
		
		if (!is_array($scope)) {
			$scope_names = array($scope);
		} else {
			$scope_names = $scope;
		}
		
		foreach ($this->sub_scopes as $sub_scope) {
			
			// compare names and add result to array
			if (in_array($sub_scope->name, $scope_names)) {
				if ($return_symbols) {
					if ($sub_scope->symbol) $scopes[] = &$sub_scope->symbol;
				} else {
					$scopes[] = $sub_scope;
				}
			}
			
			// recurse into sub scope and merge results into array
			if ((count($sub_scope->get_sub_scopes()) > 0) && ($recurse)) {
				$scopes = array_merge($scopes, $sub_scope->find_sub_scopes($scope, $return_symbols, $recurse));
			}
			
		}
		
		return $scopes;
	}

	// returns true if the scope has any symbols
	// at the current level or in nested scopes
	public function has_symbols () {
		foreach ($this->sub_scopes as $scope) {
			if ($scope->symbol) {
				return true;
			} else {
				return $scope->has_symbols();
			}
		}
	}

	// returns a flat array of all sub scopes
	public function get_sub_scopes_flat ($return_symbols = false) {
		$scopes = array();
		
		foreach ($this->sub_scopes as $sub_scope) {
			
			if ($return_symbols) {
				if ($sub_scope->symbol) $scopes[] = &$sub_scope->symbol;
			} else {
				$scopes[] = &$sub_scope;
			}

			// recurse into sub scope and merge results into array
			if (count($sub_scope->get_sub_scopes()) > 0) {
				$scopes = array_merge($scopes, $sub_scope->get_sub_scopes_flat($return_symbols));
			}
		}
		
		return $scopes;
	}
		
	// returns true if $scope is within the range of $this
	public function contains_scope (Scope $scope) {
		return $this->contains_range($scope->start, $scope->end);
	}
	
	public function contains_range ($start, $end) {
		return (($start >= $this->start) && ($end <= $this->end));
	}
	
	public function contains_offset ($offset) {
		return (($offset >= $this->start) && ($offset <= $this->end));
	}
	
	// trims the scopes start/end to be absent of whitespace
	// this is used for scopes which whitespace in the pattern
	// don't exceed line bounds which are needed to match
	// availability macros
	public function trim_range_white_space () {
		
		for ($i=0; $i < strlen($this->contents); $i++) { 
			if (preg_match("/\s+/", $this->contents[$i])) {
				$this->start += 1;
			} else {
				break;
			}
		}
		
		for ($i=strlen($this->contents) - 1; $i > -1; $i--) { 
			if (preg_match("/\s+/", $this->contents[$i])) {
				$this->end -= 1;
			} else {
				break;
			}
		}
	}
	
	// returns a string of all sub scopes symbols source
	// $indent = level of indent to apply when building source
	// $filter = an optional string (or array) of symbol classes which are allowed
	//
	// this method is inherently recursive because sub scopes
	// may also call this method when their source is built 
	public function get_sub_scope_symbol_source ($indent = 0, $filter = null, $print_header = false) {			
		
		if ($filter) {
			if (!is_array($filter)) $filter = array($filter);
		}
		
		foreach ($this->get_sub_scopes() as $scope) {
			if ($scope->symbol) {
				
				if ($filter) {
					$accept = false;
					foreach ($filter as $symbol_class) {
						if (is_a($scope->symbol, $symbol_class)) {
							$accept = true;
							break;
						}
					}
				} else {
					$accept = true;
				}
				
				if ($accept) {
					
					// print the block header if available and requested
					// and increase indent level
					if (($print_header) && ($scope->symbol->get_block_header())) {
						$source .= "\n";
						$source .= $scope->symbol->get_block_header()."\n";
						$scope->symbol->build_source($indent + $scope->symbol->get_base_indent());
						$source .= $scope->symbol->source."\n";
					} else {
						$scope->symbol->build_source($indent);
						$source .= $scope->symbol->source;
					}
				}
			}
		}
		
		return $source;
	}
			
	public function get_symbol_source ($indent = 0) {			
		if ($this->symbol) {
			$this->symbol->build_source($indent);
			return $this->symbol->source;
		}
	}
	
	private function print_symbol_dependants ($indent, Symbol $symbol, Output $output) {
		
		foreach ($symbol->dependents as $dependent) {
			
			// if the class of the depedant changed print the block header
			if (get_class($dependent) != get_class($symbol)) {
				$dependent->print_block_header(0, $output);
				if ($indent == 0) {
					$dependent->build_source($dependent->get_base_indent());
				} else {
					$dependent->build_source($indent);
				}
			} else {
				$dependent->build_source($indent);
			}
			
			$output->writeln(0, $dependent->source);
			
			// recurse into dependents
			if ($dependent->dependents) $this->print_symbol_dependants($indent, $dependent, $output);
		}
	}
		
	// prints the scopes symbol table to $output
	public function print_symbol_table (Output $output) {
		$section = null;
		$section_scope = null;
		
		foreach ($this->get_sub_scopes() as $scope) {
			$printed_header = false;
			
			if ($symbol = $scope->symbol) {
				
				// the symbol is not printable
				if (!$symbol->is_printable()) continue;
				
				// scope changed
				if ($symbol->get_scope_name() != $section_scope) {
					
					$next_section = $symbol->get_section();
					$previous_section = $section;
					
					// block changed but section is the same
					if ($next_section == $previous_section) $output->writeln(0, "", true);
					
					// section ended
					if (($section) && ($next_section != $previous_section)) {
						$output->print_section($section, false);
						$section_scope = null;
					}
					
					// section started
					if ($section = $symbol->get_section()) {
						if ($previous_section != $section) {
							$output->print_section($section, true);
						}
						$printed_header = true;
						$symbol->print_block_header(0, $output);
						$section_scope = $symbol->get_scope_name();
					}
				}
				
				// recurse into macro		
				if ($scope->conforms_to(SCOPE_MACRO)) {
					if ($symbol->pair) {
						if ($symbol->contains_printable_symbols()) {
							$output->writeln(0, $symbol->get_start_line());
							$scope->print_symbol_table($output);
							$output->writeln(0, $symbol->get_end_line());
						}
					} else {
						$output->writeln(0, $symbol->get_line());
					}
				} else {
					if (($symbol->always_print_header()) && (!$printed_header)) {
						$symbol->print_block_header(0, $output);
					}
					
					// print symbol source
					$indent = $symbol->get_base_indent();
					$symbol->build_source($indent);
					$output->writeln(0, $symbol->source);
					
					// print symbol dependents
					$this->print_symbol_dependants($indent, $symbol, $output);
				}
			} else {
				// scope has no symbol so it can not be printed 
				// but we need to recurse into macros anyways
				if ($scope->conforms_to(SCOPE_MACRO)) {
					
					// end trailing section
					if ($section) {
						$output->print_section($section, false);
						$section = null;
					}
					
					$scope->print_symbol_table($output);
				}
			}
		
		}
		
		// end trailing section
		if ($section) {
			$output->print_section($section, false);
			$section = null;
		}
	}
			
	// debugging method for printing a scopes tree
	public function print_tree () {
		print("\n");
		
		if ($this->symbol) {
			print($this->name." (".$this->symbol->name.")\n");
		} else {
			print($this->name."\n");
		}
		
		$this->print_tree_private(2);
		print("\n");
	}
	
	// reloads the uuid to a unique value
	public function reload_uuid () {
		$this->uuid = uniqid();
	}
	
	/**
	 * Private
	 */
	
	// invoked when the scope was added to another sub
	protected function added_to_scope ($scope) {
	}
		
	private function print_tree_private ($level) {
		$indent = indent_string($level)." ";
				
		foreach ($this->sub_scopes as $scope) {
			
			if ($scope->symbol) {
				print($indent.$scope->name." (".$scope->symbol->name.")\n");
			} else {
				print($indent.$scope->name."\n");
			}
			
			$scope->print_tree_private($level + 1);
		}
		
	}
	
	/**
	 * Constructors
	 */
	
	public function __toString() {
		return (string)$this->name;
	}
	
	protected function __free() {
		parent::__free();
				
		if ($this->symbol) {
			$this->symbol->free();
			unset($this->symbol);
		}
		
		MemoryManager::free_array($this->sub_scopes);
	}
		
	public function __construct($start, $end, $name, $contents, Header $header) {		
		$this->start = $start;
		$this->start_from = $start;
		$this->end = $end;
		$this->name = $name;
		$this->contents = $contents;
		$this->header = &$header;
		$this->reload_uuid();
	}
}

?>
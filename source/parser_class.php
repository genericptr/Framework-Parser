<?php

require_once("parser_module.php");
require_once("parser_method.php");
require_once("parser_ivar.php");
require_once("symbol.php");

/**
 * Class symbol
 */
class ClassSymbol extends Symbol {
	public $super_class;								// name of the super class
	public $protocols;									// array of protocol names the class conforms to
	public $methods = array();					// array of methods and macro symbols which may contain methods
	public $instance_variables;					// IVarBlockSymbol from scope

	public $adopted_methods = array();	// array of MethodSymbol (methods that are adopted from protocols we conform to)
																			// these are added to the class symbol by calling SymbolTable::adopt_class_protocols
																			// after all headers have been parsed
	
	public $categories = array();				// array of CategorySymbol categories which extend the class
	
	public $protecting_category_keywords = false;
	private $protecting_keyword = false;
	private $super_class_lookup = -1;
	private $methods_lookup = -1;
	
	/**
	 * Methods
	 */
	
	// performs a search of the symbol table for the super class (if available)
	public function find_super_class () {
		
		// ??? testing, safe to remove yet?
		if ($this->is_free()) ErrorReporting::errors()->add_exception("$this->name is freed!\n");
		
		// cache the super class symbol since this call can
		// be very expensive on CPU
		if ($this->super_class_lookup == -1) {
			$this->super_class_lookup = SymbolTable::table()->find_symbol($this->super_class, "ClassSymbol", ANY_HEADER, $this->framework, SEARCH_IMPORTED_FRAMEWORKS);
		}
		
		return $this->super_class_lookup;
	}
	
	// returns an array of all method symbols in the class
	public function find_methods () {
		if ($this->methods_lookup == -1) {
			$this->methods_lookup = $this->scope->find_sub_scopes(array(SCOPE_METHOD, SCOPE_PROPERTY), true, true); 
		}
		
		return $this->methods_lookup;
	}
	
	// finds the method in the class of the name
	public function find_method ($name, $recurse = false) {
		$methods = $this->find_methods();
		foreach ($methods as $method) {
			if ($method->name == $name) return $method;
		}
		
		// recurse into super class
		if ($recurse) {
			if ($super_class = $this->find_super_class()) {
				return $super_class->find_method($name, $recurse);
			}
		}
	}
	
	// returns an array of methods that are a duplicate (by name)
	// to the supplied method
	public function find_duplicate_methods (MethodSymbol $find_method) {
		$duplicates = array();
		
		$methods = $this->find_methods();
		foreach ($methods as $method) {
			if ($method->compare($find_method)) continue;
			if ($method->name == $find_method->name) $duplicates[] = $method;
		}
		
		// recurse into super class
		if ($super_class = $this->find_super_class()) {
			$duplicates = array_merge($duplicates, $super_class->find_duplicate_methods($find_method));
		}
		
		return $duplicates;
	}
	
	// returns true if the method has been adopted
	public function is_method_adopted ($name) {
		foreach ($this->adopted_methods as $method) {
			if ($method->name == $name) return $method;
		}
		
		if ($super = $this->find_super_class()) {
			return $super->is_method_adopted($name);
		}
	}
	
	public function add_category (CategorySymbol $category) {
		$this->categories[] = &$category;
	}
	
	public function remove_category (CategorySymbol $category) {		
		if (!$this->categories) return;
		
		foreach ($this->categories as $key => $symbol) {
			if ($symbol->uuid == $category->uuid) {
				unset($this->categories[$key]);
				//print("* remove $category->name from $this->name\n");
				return;
			}
		}
	}
	
	// protects a keyword in class and global namespace
	public function protect_keyword (&$keyword) {
		
		// protect symbol in super classes keywords
		if (!$this->protecting_keyword) {
			if ($super_class = $this->find_super_class()) $super_class->protect_keyword($keyword);
		}
		
		// protect in class namespace		
		$this->namespace->protect_keyword($keyword);
		
		// protect in global namespace
		global_namespace_protect_keyword($keyword);
		
		//print("protect $keyword in $this->name\n");
	}
	
	public function protect_method (MethodSymbol $method) {
		
		// protect symbol in super classes keywords
		if (!$this->protecting_keyword) {
			if ($super_class = $this->find_super_class()) $super_class->protect_method($method);
		}
		
		// protect method name against instance variable fields
		// but make the method name take precedence over the field
		if ($this->instance_variables) {
			$fields = $this->instance_variables->get_fields();
			foreach ($fields as $field) {
				if ($field->contains_name($method->name)) {
					$new_name = $method->name;
					$field->replace_name($method->name, protect_keyword($new_name));
				}
			}
		}
		
		// protect methods from super classes that have the same
		// name but different selector
		if ($method->class->uuid != $this->uuid) {
			foreach ($this->methods as $_method) {
				if (($method->name == $_method->name) && ($method->selector != $_method->selector)) {
					protect_keyword($method->name);
				}
			}
		} else {
			// search all methods before the method being protected
			// which are in the same as the current class
			foreach ($this->methods as $_method) {
				if ($method->compare($_method)) break;
				if (($method->name == $_method->name) && ($method->uuid != $_method->uuid)) {
					protect_keyword($method->name);
				}
			}
		}
				
		// protect in global namespace
		global_namespace_protect_keyword($method->name);
	}
	
	protected function add_method_source ($source, $indent, $public = false) {
		if ($this->methods) {
			if ($public) $source .= indent_string($indent - 1)."public\n";
			foreach ($this->methods as $method) {
				
				// if the symbol is a macro then we need filter
				// out only methods and other macros because there
				// may have been nested structs, enums etc... in the class
				if (is_a($method, "MacroSymbol")) {
					$method->build_source_and_filter($indent, array("MethodSymbol", "MacroSymbol"));
				} else {
					$method->build_source($indent);
				}
				
				$source .= $method->source;
			}
		}
		return $source;
	}	
	
	public function finalize () {
		//print("finalizing class $this->name\n");
		
		// unprotect all previously protected methods
		// since the class will be finalized and all methods
		// protected for the final time
		foreach ($this->methods as $method) {
			$this->namespace->remove_keyword($method->name);	
		}
		
		// renamed class methods that have duplicate
		// instance methods by prefixing 
		foreach ($this->methods as $method) {
			if ($method->is_class) continue;
			if (!is_a($method, "MethodSymbol")) continue;
			if ($duplicates = $this->find_duplicate_methods($method)) {	
				foreach ($duplicates as $duplicate) {
					if ($duplicate->is_class) {
						$duplicate->class->namespace->remove_keyword($duplicate->name);
						$duplicate->name = unprotect_keyword($duplicate->name);
						$duplicate->name = DUPLICATE_CLASS_METHOD_PREFIX.ucfirst($duplicate->name);
					}
				}
			}
		}
		
	}
	
	public function get_section () {
		return HEADER_SECTION_CLASSES;
	}
	
	public function get_base_indent () {
		return 1;
	}
	
	public function always_print_header () {
		return true;
	}
	
	public function print_block_header ($indent, Output $output) {
		$output->writeln(0, "");
		$output->writeln(0, "type");
	}
		
	public function build_source ($indent = 0) {
		//$source = indent_string($indent)."type\n";
		//$indent += 1;
		
		$source .= indent_string($indent).$this->name." = ".DECLARED_CLASS_KEYWORD;
		
		// add super class section
		if ($this->super_class || $this->protocols) {
			$source .= " (";
			
			// add superclass
			if ($this->super_class) $source .= $this->super_class;
			
			// add protocols
			if ($this->protocols) {
				if ($this->super_class) {
					$source .= ", ".implode(", ", $this->protocols);
				} else {
					$source .= implode(", ", $this->protocols);
				}
			}
			
			$source .= ")\n";
		} else {
			$source .= "\n";
		}
		
		// add instance variables
		// the source has already been built from fields when the symbol was created
		// so all we need to do is add it the source string now
		if ($this->instance_variables) {
			$source .= indent_string($indent)."private\n";
			$this->instance_variables->build_source($indent + 1);
			$source .= $this->instance_variables->source;
		}
		
		// add methods
		$source = $this->add_method_source($source, $indent + 1, true);
		
		// adopt protocol methods
		if ($this->adopted_methods) {
			$source .= "\n";
			$source .= indent_string($indent + 1)."{ Adopted protocols }\n";
			foreach ($this->adopted_methods as $method) {
				$method->build_source($indent + 1);
				$source .= $method->source;
			}
		}
		
		// end class declaration
		$source .= indent_string($indent)."end;\n";
		
		$this->source = $source;
	}
		
	protected function __free() {

		if (is_a($this->super_class_lookup, "ClassSymbol")) {
			unset($this->super_class_lookup);
		}
		
		unset($this->methods);
		unset($this->methods_lookup);
		unset($this->adopted_methods);
		unset($this->categories);
		unset($this->instance_variables);
		
		parent::__free();
	}
	
}

/**
 * Section symbol
 */
class SectionSymbol extends Symbol {
	public function build_source ($indent = 0) {
		$this->source = indent_string($indent - 1).$this->name."\n";
	}
}

/**
 * Class section parser
 */
class HeaderClassSectionParser extends HeaderParserModule {
	private $pattern_section = array(	"id" => 1, 
																		"scope" => SCOPE_KEYWORD, 
																		"pattern" => "/@(public|private|protected|optional|required|package)+[;]*/i",
																		);
	
	function process_scope ($id, Scope $scope) {
		//print("got class section at $scope->start/$scope->end\n");
		//print($scope->contents."\n");
		
		$section = new SectionSymbol($this->header);
		$section->name = $scope->results[1];
		
		// @package does not exist so we replace with private
		if ($section->name == "package") $section->name = "private";
		
		$scope->set_symbol($section);
	}
	
	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_section);
	}		
	
}

/**
 * Class forward symbol
 */

class ClassForwardSymbol extends Symbol {
	public $classes = array();
	public $is_protocol = false;
	
	public function is_declarable () {
		return false;
	}
	
	public function is_printable () {
		return false;
	}
	
}

/**
 * Class forward parser
 */
class HeaderClassForwardParser extends HeaderParserModule {
	private $pattern_forward = array(	"id" => 1, 
																		"scope" => SCOPE_FORWARD, 
																		"pattern" => "/@(class|protocol)+\s+([^\n]+);/i",
																		);
	
	function process_scope ($id, Scope $scope) {
		//print("got class forward at $scope->start/$scope->end\n");
		//print($scope->contents."\n");
		
		$forward = new ClassForwardSymbol($this->header);
		$forward->name = trim($scope->results[2]);
		$forward->classes = preg_split("/\s*,\s*/", $forward->name);
		
		// if the forward is a protocol suffix names
		// with protocol suffix
		if ($scope->results[1] == "protocol") {
			$forward->is_protocol = true;
			foreach ($forward->classes as $key => $class) {
				$forward->classes[$key] .= PROTOCOL_SUFFIX;
			}
		}
		
		// add all forwards as implicit pointers
		SymbolTable::table()->add_implicit_pointer($forward->classes);
		
		foreach ($forward->classes as $name) $this->symbols->add_declared_type($name);
		
		$this->symbols->add_symbol($forward);
		
		$scope->set_symbol($forward);
	}
	
	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_forward);
	}		
	
}

/**
 * Class Scope
 */

class ClassScope extends Scope {
	
	protected function added_to_scope ($scope) {
		// when the class scope has been added get the name 
		// and add it as an implicit pointer so all subsequent
		// methods/ivars will format pointer types properly
		$name = $this->start_results[1];
		SymbolTable::table()->add_implicit_pointer($name);
	}
	
}

// register scope class
Scope::add_class_for_scope(SCOPE_CLASS, "ClassScope");

/**
* Class parser
*/
class HeaderClassParser extends HeaderParserModule {
		
	private $pattern_class = array(	"id" => 1, 
																	"scope" => SCOPE_CLASS, 
																	"start" => "/@interface\s+(\w+)\s*:\s*(\w+)\s*(<(.*?)>)*/i",
																	"end" => "/@end/i",
																	"modules" => array(	MODULE_MACRO, MODULE_IVAR, MODULE_CLASS_SECTION, MODULE_METHOD, MODULE_PROPERTY,
																											MODULE_STRUCT, MODULE_ENUM, MODULE_TYPEDEF,
																											),
																	PATTERN_KEY_LOCATION_OFFSET => true,
																	);
	
	
	private $pattern_class_no_super = array(	"id" => 2, 
																						"scope" => SCOPE_CLASS, 
																						"start" => "/@interface\s+(\w+)\s*(<(.*?)>)*/i",
																						"end" => "/@end/i",
																						
																						"modules" => array(	MODULE_MACRO, MODULE_IVAR, MODULE_CLASS_SECTION, MODULE_METHOD, MODULE_PROPERTY,
																																MODULE_STRUCT, MODULE_ENUM, MODULE_TYPEDEF,
																																),
																						PATTERN_KEY_LOCATION_OFFSET => true,
																						);
					
	/**
	 * Methods
	 */
			
	// parses the protocols the class conforms to (in the <> section)
	// $source = raw protocol list from within <>
	private function parse_protocols ($source, ClassSymbol &$class) {
		$list = explode(",", $source);
		
		foreach ($list as $protocol) {
			$protocol = trim($protocol);
			
			// append the protocol suffix for pascal name compatibility
			$protocol .= PROTOCOL_SUFFIX;
			
			$class->protocols[] = $protocol;
		}
	}
	
	// in some classes the protocol section has been broken by
	// macros and can't be captured since it conflicts with the
	// instance variable pattern so we perform a last check to
	// find any declarations that may have been missed.
	private function find_protocols (Scope $scope, ClassSymbol $class) {
		$lines = explode("\n", $scope->contents);
		array_shift($lines);
		
		foreach ($lines as $line) {
			
			// found a protocol section
			if (preg_match("/^\s*<(.*)>/", $line, $captures)) {
				$this->parse_protocols($captures[1], $class);
				break;
			}
			
			// found methods, ivars or class section, break
			if (preg_match("/^\s*[-+{@]+/", $line)) break;
			
		}
	}
	
	private function process_class ($name, $super_class, $protocols, Scope $scope) {
		$class = new ClassSymbol($this->header);
		
		// build class array
		$class->name = $name;
		if ($super_class) $class->super_class = $super_class;
		
		// parse protocol list (optional)
		if ($protocols) {
			$this->parse_protocols($protocols, $class);
		} else {
			$this->find_protocols($scope, $class);
		}
		
		// if no super class is available use root class
		if ((!$class->super_class) && ($class->name != ROOT_CLASS)) $class->super_class = ROOT_CLASS;
		
		// add instance variables from the scope
		if ($symbols = $scope->find_sub_scopes(SCOPE_IVAR_BLOCK, true, false)) {
			$class->instance_variables = $symbols[0];
		}
		
		// add methods from the scope
		if ($symbols = $scope->find_sub_scopes(array(SCOPE_METHOD, SCOPE_PROPERTY, SCOPE_MACRO), true, false)) {
			$class->methods = $symbols;
		}		
		
		// set the scope symbol
		$class->set_scope($scope);
		
		// notify each method it was added to the class
		if ($methods = $class->find_methods()) {
			foreach ($methods as $method) {
				$method->added_to_class($class);
			}
		}

		return $class;
	}						
		
	function process_scope ($id, Scope $scope) {
		// print("got class pattern $id\n");
		// print($scope->contents);
		// print_r($scope->start_results);
		
		$results = $scope->start_results;
		
		switch ($id) {
			
			case 1: {		
				if ($class = $this->process_class($results[1], $results[2], $results[4], $scope)) {
					$this->symbols->add_symbol($class);
					$scope->set_symbol($class);
				}
				break;
			}
			
			case 2: {		
				if ($class = $this->process_class($results[1], null, $results[3], $scope)) {
					$this->symbols->add_symbol($class);
					$scope->set_symbol($class);
				}
				break;
			}
			
		}
	}
	
	function conclude () {
		//$this->symbols->show();
		//die("finished parsing classes");
	}
		
	public function init () {
		parent::init();

		$this->add_pattern($this->pattern_class);
		$this->add_pattern($this->pattern_class_no_super);
	}		

}
		

?>
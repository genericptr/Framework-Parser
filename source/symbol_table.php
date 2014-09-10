<?php

require_once("symbol.php");
require_once("language_utilities.php");
require_once("memory_manager.php");

/**
 * Defines
 */

define("SYMBOL_NAME_PATTERN", "/^[a-z_]+\w+$/i");	// the pattern to verify symbol names against

define("ANY_CLASS", null);
define("ANY_HEADER", null);
define("ANY_FRAMEWORK", null);

define("SEARCH_IMPORTED_FRAMEWORKS", true);

/**
 * Tables
 */

// GenericTable and subclasses are meant to hold the actual symbols
// and provide lookup utilities so that the master symbol table class
// SymbolTable can manage the sub tables easier and provide faster searching.

class GenericTable extends MemoryManager {
	public $symbols = array();
		
	public function find_all_symbols ($classes = null) {
		if (!$classes) {
			$symbols = array();
			foreach ($this->symbols as $class_symbols) {
				$symbols = array_merge($symbols, $class_symbols);
			}
			return $symbols;
		} else {
			if (!is_array($classes)) $classes = array($classes);
			$symbols = array();

			foreach ($classes as $class) {
				if ($class_symbols = $this->symbols[$class]) {
					$symbols = array_merge($symbols, $class_symbols);
				}
			}

			return $symbols;
		}
		
	}
	
	public function print_table () {
		foreach ($this->symbols as $class => $symbols) {
			foreach ($symbols as $symbol) print("$symbol->name\n");
		}
	}
	
	public function add_symbol (Symbol $symbol) {
		$symbol->added_to_table($this);
		$this->symbols[get_class($symbol)][] = &$symbol;
	}
	
	public function remove_symbol (Symbol $symbol) {
		//print("  [*] remove $symbol->name from ".get_class($this)."\n");
		foreach ($this->symbols as $class => $symbols) {
			foreach ($symbols as $symbol_key => $_symbol) {
				if ($symbol->uuid == $_symbol->uuid) {
					MemoryManager::free_array_value($this->symbols[$class], $symbol_key);
					return;
				}
			}
		}
	}
		
	public function remove_symbols () {
		foreach ($this->symbols as $class => $symbols) {
			foreach ($symbols as $key => $symbol) {
				//print("  [*] remove $symbol->name from ".get_class($this)."\n");
				MemoryManager::free_array_value($this->symbols[$class], $key);
			}
		}
	}
		
}

class FrameworkTable extends GenericTable {
	public $framework;
		
	function __construct(Framework $framework) {
		$this->framework = &$framework;
	}
}

class HeaderTable extends FrameworkTable {
	public $header;
		
	function __construct(Header $header) {
		$this->header = &$header;
		$this->framework = &$header->framework;
	}
}

/**
 * Master Symbol Table
 */

// The symbol table manages namespace utilities and tables of
// symbols across multiple headers and frameworks that have been
// parsed.
//
// There's probably lots of room to improve memory management but
// currently heaps of memory are generated during parsing so
// we try our best to simply remove the frameworks from the table
// when they no longer have any inter-dependencies on other frameworks
//
// The reason we keep a master symbol instead of parsing framework by
// framework is so that classes and types from one framework can view the 
// symbols of other frameworks for keyword protection, protocol adopting etc...

class SymbolTable {
	
	private static $instance;
	
	// Symbol Types
	const TYPE_UNKNOWN = 0;		// meta-value, no symbols should ever use this type
	const TYPE_STRUCT = 1;
	const TYPE_TYPEDEF = 2;
	const TYPE_ENUM = 3;
	const TYPE_FUNCTION = 4;
	const TYPE_VARIABLE = 5;
	const TYPE_DEFINE = 6;
	const TYPE_CLASS = 7;
	const TYPE_PROTOCOL = 8;
	const TYPE_CATEGORY = 9;
	const TYPE_METHOD = 10;
	
	// symbol table
	private $used_types = array();						// array of types that have been used (i.e by fields or parameters)
	private $declared_types = array();        // array of types that have been declared
	public $implicit_pointers = array();			// array of types that are implicit pointers
	
	private $frameworks = array();						// array of FrameworkTable
	private $headers = array();								// array of HeaderTable
	
	/**
	 * Utilities
	 */
	
	
	// returns the global symbol table using SymbolTable::table() 
	public static function table () {
		if (!isset(self::$instance)) {
			$class_name = __CLASS__;
	    self::$instance = new $class_name;
		}
    
		return self::$instance;
	}
		
	/**
	 * Printing
	 */
	
	// prints all pointers to classes/protocols in the header
	public function print_class_pointers (Header $header, $output) {
		if ($symbols = $this->find_all_symbols(array("ClassSymbol", "ProtocolSymbol"), $header)) {
			if (count($symbols) > 0) {
				$output->writeln(0, "");
				$output->print_section("TYPES", true);
				$output->writeln(0, "type");
				foreach ($symbols as $symbol) $output->writeln(1, $symbol->name.POINTER_SUFFIX." = ^$symbol->name;\n");
				$output->print_section("TYPES", false);
			}
		}
	}
	
	// prints all types that have been nested within class/category declarations
	public function print_nested_class_types (Header $header, $output) {
		if ($symbols = $this->find_all_symbols(array("ClassSymbol", "CategorySymbol", "ProtocolSymbol"), $header)) {
			foreach ($symbols as $symbol) {
				$types = $symbol->get_scope()->find_sub_scopes(array(SCOPE_MACRO, SCOPE_BLOCK_CONST, SCOPE_TYPE), true, false);
				
				if (count($types) > 0) {
					$output->writeln();
					$output->writeln(0, "{ Types from ".$symbol->name." }");
					$output->print_section("TYPES", true);
					
					foreach ($types as $type) {
						
						// build source
						if ($type->is_from_scope(SCOPE_MACRO)) {
							$type->build_source_and_filter($indent, array("TypedefSymbol", "EnumBlockSymbol", "StructSymbol"), MACRO_BUILD_SOURCE_NOT_EMPTY, MACRO_BUILD_SOURCE_PRINT_HEADERS);
						} else {
							if ($type->is_printable()) {
								$output->writeln();
								if ($type->get_block_header()) {
									$type->print_block_header(0, $output);
									$type->build_source($indent + 1);
								} else {
									$type->build_source($indent);
								}
							}
						}
						
						// print source
						$output->writeln(0, $type->source);
						
						// print dependents
						foreach ($type->dependents as $dependent) {
							if ($dependent->get_block_header()) {
								$dependent->print_block_header(0, $output);
								$dependent->build_source($indent + 1);
							} else {
								$dependent->build_source($indent);
							}
							$output->writeln(0, $dependent->source);
						}
						
					}
					
					$output->print_section("TYPES", false);
				}
				
			}
		}
	}

	// prints all typedefs that are declared as callbacks (from HeaderFunctionParser::add_callback)
	public function print_callbacks (Header $header, $output) {
		
		// find all typedefs which are callbacks
		$callbacks = array();
		if ($symbols = $this->find_all_symbols("TypedefSymbol", $header)) {
			foreach ($symbols as $symbol) {
				if ($symbol->is_callback) $callbacks[] = $symbol;
			}
		}
		
		if (count($callbacks) > 0) {
			$output->writeln();
			$output->writeln(0, "{ Callbacks }");
			$output->print_section("TYPES", true);
			$output->writeln(0, "type");
			foreach ($callbacks as $symbol) {
				$symbol->build_source(1);
				$output->writeln(0, $symbol->source);
			}
			$output->print_section("TYPES", false);
		}
	}
		
	public function print_inline_array_types (Header $header, $output) {
		if ($symbols = $this->find_all_symbols("InlineArrayTypedefSymbol", $header)) {
			$output->writeln();
			$output->writeln(0, "{ Inline Arrays }");
			$output->print_section("TYPES", true);
			$output->writeln(0, "type");
			
			foreach ($symbols as $symbol) {
				$symbol->build_source(1);
				$output->writeln(0, $symbol->source);
			}
			
			$output->print_section("TYPES", false);
		}
		
	}	
			
	// prints all undeclared types to an include file
	// these types exist in the framework but are not declared and therefor "opaque"
	// so they are treated as generic pointers
	public function print_opaque_types ($directory) {
		ErrorReporting::errors()->add_message("Printing opaque types...");
		
		$path = $directory."/OpaqueTypes.inc";
		if ($handle = fopen($path, "w+")) {
			
			$types = $this->find_undeclared_types();

			// declare base types
			fwrite($handle, "OpaqueType = Pointer;\n");
			fwrite($handle, "OpaqueRecordType = record end;\n");
			fwrite($handle, "\n");

			// add all types
			foreach ($types as $type) {
				switch ($type["type"]) {
					
					case self::TYPE_STRUCT:
						fwrite($handle, $type["name"]." = OpaqueRecordType;\n");
						break;
					
					default:
						fwrite($handle, $type["name"]." = OpaqueType;\n");
						break;
				}
				
				//ErrorReporting::errors()->add_note("The opaque type ".$type["name"]." was added.");
			}
			
		} else {
			ErrorReporting::errors()->add_fatal("The OpaqueTypes.inc file could not be opened for writing at \"$path\".");
		}
	}		
	
	/**
	 * Symbol Utilities
	 */
	
	// returns a single symbol matching $name
	// you should specify at least $header or $framework or
	// the search may be slow depending on how many frameworks
	// were parsed in the symbol table
	public function find_symbol ($name, $class = null, Header $header = null, Framework $framework = null, $search_imported_frameworks = false) {
		$symbols = $this->find_all_symbols($class, $header, $framework, $search_imported_frameworks);
		foreach ($symbols as $symbol) {
			if (strcasecmp($symbol->name, $name) == 0) {
				return $symbol;
			}
		}
	}
			
	// returns an array of symbols that match the given criteria
	// all parameters may be null which will return all symbols in the table
	// 
	// $class = array of symbol classes to match
	// $header = the header to search in ($framework must be null) 
	// $framework = the framework to search in ($header must be null)
	// $search_imported_frameworks = if $framework is specified imported frameworks will be search also
	public function find_all_symbols ($class = null, Header $header = null, Framework $framework = null, $search_imported_frameworks = false) {
		$symbols = array();
		
		// searching the entire table is not allowed
		if ((!$header) && (!$framework)) {
			ErrorReporting::errors()->add_exception("SymbolTable::find_all_symbols must specify a valid header or framework.");
		}
		
		if (!is_array($class) && ($class)) $class = array($class);
		
		// merge imported frameworks into array for searching
		if (($search_imported_frameworks) && ($framework)) {
			$frameworks = array($framework);
			$imported_frameworks = $framework->get_imported_frameworks();
			if (count($imported_frameworks) > 0) {
				$frameworks = array_merge($frameworks, $imported_frameworks);
			}
		} elseif ($framework) {
			$frameworks = array($framework);
		}
				
		// search header
		if (($header) && (!$framework)) {
			if ($table = $this->find_header_table($header)) {
				$symbols = $table->find_all_symbols($class);
			}
			return $symbols;
		}
		
		// search frameworks
		if (($frameworks) && (!$header)) {
			foreach ($frameworks as $framework) {
				if ($table = $this->find_framework_table($framework)) {
					$symbols = array_merge($symbols, $table->find_all_symbols($class));
				}
			}
			return $symbols;
		}
		
	}
	
	private function find_framework_table (Framework $framework) {
		foreach ($this->frameworks as $table) {
			if ($table->framework == $framework) return $table;
		}
	}
	
	private function find_header_table (Header $header) {
		foreach ($this->headers as $table) {
			if ($table->header == $header) return $table;
		}
	}
		
	// returns an array of all types that were used by not declared (i.e. missing)
	private function find_undeclared_types () {
		$types = array();
		
		// filter out known c types from the parsers array
		// how do we account for types from other frameworks?
		// there really isn't anyway to know which will make this
		// return tons of types that exist!
		
		// compare all used types against declared types
		foreach ($this->used_types as $name => $type) {
			if (!$this->is_type_declared($name)) {
				$types[] = array("name" => $name, "type" => $type);
			}
		}
		
		return $types;
	}
				
	/**
	 * Adding Symbols
	 */
	
	public function add_symbol (Symbol $symbol) {
		if ((MESSAGE_ADD_SYMBOL) && ($symbol->name)) ErrorReporting::errors()->add_message("  + $symbol->name");
		//return;
		
		// the frameworks doesn't accept these symbols
		if ($symbol->framework->ignore_type($symbol->name)) return;
		
		// add to framework table
		if (!$table = $this->find_framework_table($symbol->framework)) {
			$table = new FrameworkTable($symbol->framework);
			$this->frameworks[] = $table;
		}
		$table->add_symbol($symbol);
		
		// add to header table
		if (!$table = $this->find_header_table($symbol->header)) {
			$table = new HeaderTable($symbol->header);
			$this->headers[] = $table;
		}
		$table->add_symbol($symbol);
		
		// declare the type
		if ($symbol->is_declarable()) $this->add_declared_type($symbol->name);
	}
	
	/**
	 * Namespace Utilities
	 */
	
	// global list of all types that were used in any declarations or variables
	// across all namespaces/scopes
	public function add_used_type ($name, $type = 0) {
		if (!$name) return;
		if (!preg_match(SYMBOL_NAME_PATTERN, $name)) return;
		
		if (!$this->is_type_used($name)) {
			$this->used_types[$name] = $type;
			//ErrorReporting::errors()->add_note("The type $name was used.");
		}
	}

	// global list of all types (identifiers) that were declared in global namespace
	public function add_declared_type ($name, $type = 0) {
		if (!$name) return;
		if (!preg_match(SYMBOL_NAME_PATTERN, $name)) return;
		
		if (!$this->is_type_declared($name)) {
			$this->declared_types[$name] = $type;
			//ErrorReporting::errors()->add_note("The type $name was declared.");
		}
	}
	
	public function add_implicit_pointer ($type) {
		if (!is_array($type)) $type = array($type);
		$this->implicit_pointers = array_merge($this->implicit_pointers, $type);
		$this->implicit_pointers = array_unique($this->implicit_pointers);
	}
	
	// returns true if the type has been declared in global namespace
	public function is_type_declared ($name) {
		return array_key_exists($name, $this->declared_types);
	}
	
	// returns true if the identifier has been declared in the framework
	// $scope = an optional scope which the identifier must be declared within
	public function is_identifier_declared ($name, Framework $framework, Scope $scope = null) {
		if ($symbol = $this->find_symbol($name, ANY_CLASS, ANY_HEADER, $framework, false)) {
			if ($scope) {
				//print("found $symbol->name ".$symbol->declared_scope_uuid." in ".$scope->uuid." ".$scope->name."\n");
				
				// the matching symbol is not declarable and therefore
				// can not present identifier conflicts
				if (!$symbol->is_declarable()) return false;
				
				return $symbol->is_declared_in_scope($scope);
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	
	// returns true if the type has been used in any namespace
	public function is_type_used ($name) {
		return array_key_exists($name, $this->used_types);
	}
	
	public function is_type_implicit_pointer ($type) {
		return in_array($type, $this->implicit_pointers);
	}
	
	// returns true if a symbol with the same name as $symbol
	// has been declared globally
	public function is_symbol_declared (Symbol $symbol) {
		$symbols = $this->find_all_symbols(ANY_CLASS, ANY_HEADER, $symbol->header->framework, SEARCH_IMPORTED_FRAMEWORKS);
		foreach ($symbols as $_symbol) {
			if ($_symbol->is_duplicate_of($symbol)) return true;
		}
	}
		
	/**
	 * Methods
	 */
	
	// clears all symbols/types in the table
	public function remove_all () {
		$this->used_types = array();
		$this->declared_types = array();
		$this->headers = array();
		$this->frameworks = array();
		$this->implicit_pointers = array();		
	}
	
	// removes the framework from all associated tables
	// freeing memory. only remove frameworks if they have no dependencies
	public function remove_framework (Framework $framework) {		
		
		foreach ($this->frameworks as $key => $table) {
			if ($table->framework->get_name() == $framework->get_name()) {
				$table->remove_symbols();
				unset($this->frameworks[$key]);
			}
		}

		foreach ($this->headers as $key => $table) {
			if ($table->framework->get_name() == $framework->get_name()) {
				$table->remove_symbols();
				unset($this->headers[$key]);
			}
		}
	}

	// resolves dependancies for symbols in the header
	// if there are unresolved dependancies framework-wide which cause
	// conflicts they are not moved because they will 
	// risk destroying the order of the headers (i.e. hundreds
	// of types would be moved after NSInteger in NSObjCRutime.inc)
	public function resolve_dependancies (Header $header) {
		$symbols = $this->find_all_symbols(ANY_CLASS, $header);
		foreach ($symbols as $symbol) {
			// symbol has a valid scope and dependencies
			// iterate the entire list
			if (($symbol->has_dependencies()) && ($symbol->has_scope())) {
				foreach ($symbol->dependencies as $dependency) {
					//print("$symbol depends on $dependency\n");
					
					// find the dependent in the header
					if ($dependent = $this->find_symbol($dependency, ANY_CLASS, $header)) {
						// the dependent was declared above the symbol
						if ($symbol->get_offset() < $dependent->get_offset()) {
							ErrorReporting::errors()->add_message("  ".$header->get_actual_name().": $symbol->name is being moved after $dependent->name");
							$symbol->remove_from_scope();
							$symbol->remove_from_tables();
							$dependent->add_dependent($symbol);
						}
					}
				}
			}
		}
	}
	
	// "adopts" methods from all classes that conform to protocols 
	public function adopt_class_protocols (Framework $framework) {
		$classes = $this->find_all_symbols("ClassSymbol", ANY_HEADER, $framework);
		foreach ($classes as $class) {
			
			// class has no protocols to adopt
			if (!$class->protocols) continue;
			
			foreach ($class->protocols as $conforms_to) {
				//print("class $class->name conforms to $conforms_to\n");
				if ($protocol = $this->find_symbol($conforms_to, "ProtocolSymbol", ANY_HEADER, $framework, SEARCH_IMPORTED_FRAMEWORKS)) {
					$class->adopted_methods = array_merge($class->adopted_methods, $protocol->get_adoptable_methods());
				}
			}
												
			// remove duplicates and sort
			$class->adopted_methods = array_unique($class->adopted_methods, SORT_STRING);
			sort($class->adopted_methods, SORT_STRING);
			//foreach ($class->adopted_methods as $method) print("    + $method in $class\n");
			
			// remove adopted methods that are duplicates
			// of existing methods in current class
			foreach ($class->adopted_methods as $key => $method) {
				if ($found = $class->find_method($method->name, true)) {
					if ($found->is_class && !$method->is_class) {
						// the methods have the same name but the existing
						// method is class so rename it by prefixing
						$found->name = unprotect_keyword($found->name);
						$found->name = DUPLICATE_CLASS_METHOD_PREFIX.ucfirst($found->name);
					} else {
						// both methods are the same kind (class or instance) 
						// so just remove the duplicate
						unset($class->adopted_methods[$key]);
					}
				}
			}
			
			// remove adopted methods that have already been adopted
			// in the classes super classes
			foreach ($class->adopted_methods as $key => $method) {
				if ($super = $class->find_super_class()) {
					if ($super->is_method_adopted($method->name)) unset($class->adopted_methods[$key]);
				}
			}
						
		}
	}
	
	// removes defines that reference symbols which can not be defined
	// i.e. declaring an external variable as a constant which is not
	// allowed in pascal but permitted in c defines
	public function resolve_defined_symbol_conflicts (Framework $framework) {
		$symbols = $this->find_all_symbols("DefineSymbol", ANY_HEADER, $framework);
		foreach ($symbols as $symbol) {
			if ($symbol->is_value_word()) {
				if ($defined_symbol = $this->find_symbol($symbol->value, ANY_CLASS, ANY_HEADER, $framework, SEARCH_IMPORTED_FRAMEWORKS)) {
					if (!$defined_symbol->can_become_constant()) {
						ErrorReporting::errors()->add_message("  $symbol->name can not define $defined_symbol->name");
						$symbol->remove_from_scope();
						$symbol->remove_from_tables();
					}
				}
			}
		}
	}
	
	// finalizes all classes/categories by letting them post process
	// after all classes have been processed and the class
	// hierarchy for the framework fully constructed
	public function finalize_symbols (Framework $framework) {
		
		// framework can't be finalized, bail
		if (!$framework->can_finalize()) return;
		
		ErrorReporting::errors()->add_message("  Finalizing classes...");
				
		// classes
		$classes = $this->find_all_symbols("ClassSymbol", ANY_HEADER, $framework);
		
		foreach ($classes as $class) {
				
			// finalize class
			$class->finalize();	
				
			// finalize instance variables
			if ($class->instance_variables) $class->instance_variables->finalize($class);
			
			// finalize methods
			$methods = $class->find_methods();
			foreach ($methods as $method) {
				$method->finalize($class);
			}
		}
		
		ErrorReporting::errors()->add_message("  Finalizing categories...");
		
		// categories
		$categories = $this->find_all_symbols("CategorySymbol", ANY_HEADER, $framework);
		foreach ($categories as $category) {
			
			// finalize category
			$category->finalize();
			
			// finalize methods
			$methods = $category->find_methods();
			foreach ($methods as $method) {
				$method->finalize($category);
			}
		}
		
		ErrorReporting::errors()->add_message("  Finalizing protocols...");
		
		// protocols
		$protocols = $this->find_all_symbols("ProtocolSymbol", ANY_HEADER, $framework);
		foreach ($protocols as $protocol) {
			
			// finalize methods
			$methods = $protocol->find_methods();
			foreach ($methods as $method) {
				$method->finalize($protocol);
			}
		}
		
		ErrorReporting::errors()->add_message("  Finalizing records...");
		
		// structs
		$structs = $this->find_all_symbols("StructSymbol", ANY_HEADER, $framework);
		foreach ($structs as $struct) {
			$struct->finalize();
		}
		
		ErrorReporting::errors()->add_message("  Finalizing types...");
		
		// typedefs
		$typedefs = $this->find_all_symbols("TypedefSymbol", ANY_HEADER, $framework);
		foreach ($typedefs as $typedef) {
			$typedef->finalize();
		}
		
	}
		
	public function build_formal_declarations (Framework $framework, &$defined, &$declared, &$all) {
		
		$defined_classes = array();
		$declared_classes = array();
		
		$defined_protocols = array();
		$declared_protocols = array();
		
		//$all_classes = array();
		
		$symbols = $this->find_all_symbols("ClassSymbol", ANY_HEADER, $framework);
		foreach ($symbols as $class) {
			$defined_classes[] = $class->name;
		}
		
		$symbols = $this->find_all_symbols("ProtocolSymbol", ANY_HEADER, $framework);
		foreach ($symbols as $protocol) {
			$defined_protocols[] = $protocol->name;
		}
		
		$symbols = $this->find_all_symbols("ClassForwardSymbol", ANY_HEADER, $framework);
		foreach ($symbols as $forward) {
			foreach ($forward->classes as $name) {
				if ($forward->is_protocol) {
					if (!in_array_str($name, $defined_protocols)) $declared_protocols[] = $name;
				} else {
					if (!in_array_str($name, $defined_classes)) $declared_classes[] = $name;
				}
			}
		}
		
		$defined_classes = array_unique($defined_classes);
		$declared_classes = array_unique($declared_classes);
		sort($defined_classes);
		sort($declared_classes);
		
		$defined_protocols = array_unique($defined_protocols);
		$declared_protocols = array_unique($declared_protocols);
		sort($defined_protocols);
		sort($declared_protocols);
		
		// make master list
		$all = array();
		$all = array_merge($all, $defined_classes);
		$all = array_merge($all, $declared_classes);
		$all = array_unique($all);
		sort($all);

		// return final values
		$defined["classes"] = $defined_classes;
		$defined["protocols"] = $defined_protocols;
		
		$declared["classes"] = $declared_classes;
		$declared["protocols"] = $declared_protocols;
	}
	
	public function print_table () {
	}			
			
	function __construct() {
		global $standard_c_types;
		
		// declare standard c types
		foreach ($standard_c_types as $type) {
			$this->add_declared_type($type);
		}
		
	}

}

?>
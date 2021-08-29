<?php

require_once("errors.php");
require_once("scope.php");
require_once("namespace.php");
require_once("memory_manager.php");

define("DEFAULT_SYMBOL_NAME", "__NAME__");

/**
* Base class for all symbols
*/
class Symbol extends MemoryManager {
	
	// required
	public $name;					// name of the symbol.
	public $source;				// pascal source for printing.
	public $header;				// the header we belong to
	public $framework;		// the framework we belong to
	
	public $scope;				// the scope we belong to
												// each symbol has it's own scope to get the scope which the symbol
												// is declared in use get_parent_scope()
	public $uuid;					// unique id of the symbol
	public $namespace;		// namespace of the symbol
	
	// optional
	public $dependencies = array();			// array of symbols which we depend on for compiling (i.e. must be declared below this symbol)
	public $dependents = array();				// array of symbols which depend on the symbol and must be declared below us
	public $deprecated_macro;

	private $tables = array();					// array of symbol tables the symbol belongs to
	private $declared_scope_uuid;				// uuid of the scope at the time the symbol was declared
																			// this value is used for comparing scopes in is_declared_in_scope()
	
	/**
	 * Methods
	 */

	public function is_fully_defined(): bool {
		return true;
	}

	public function set_scope (Scope $scope) {
		$this->declared_scope_uuid = $scope->get_super_scope()->uuid;
		$this->scope = &$scope;
		$this->added_to_scope($scope);
		//print("$this->name is declared in $this->declared_scope_uuid ".$scope->get_super_scope()->name."\n");
		
		// set the scope of all dependents also
		foreach ($this->dependents as $dependent) {
			$dependent->set_scope($scope);
		}
	}
	
	public function has_scope () {
		return ($this->scope != null);
	}
	
	// invoked when the symbol has been added to a scope
	protected function added_to_scope (Scope $scope) {
	}
	
	// invoked when the symbol has been added to a table
	// NOTE: when overriding always invoke parent::added_to_table()
	public function added_to_table (GenericTable $table) {
		$this->tables[] = &$table;
	}
	
	// removes the symbol from symbol tables it has been added to
	public function remove_from_tables () {
		foreach ($this->tables as $table) {
			$table->remove_symbol($this);
		}
	}
	
	// removes the symbol from the scope its defined to
	public function remove_from_scope () {
		if ($this->has_scope()) $this->scope->set_symbol(null);
	}
	
	public function get_scope () {
		return $this->scope;
	}
	
	public function get_scope_name () {
		return $this->scope->name;
	}
	
	// returns the offset the symbol was declared at in the scope
	public function get_offset () {
		return $this->scope->start;
	}
	
	// override and return false if the symbol
	// can not be set a declared in the symbol table
	public function is_declarable () {
		return true;
	}
	
	// returns true if the symbol is from the scope (by name)
	public function is_from_scope ($scope) {
		return $this->scope->conforms_to($scope);
	}
	
	// returns true if the symbol was declared in the scope
	public function is_declared_in_scope (Scope $scope) {
		return ($this->declared_scope_uuid == $scope->uuid);
	}
	
	// returns the parent scope which the symbol was declared in
	// $this->scope is the scope of the symbol but not the scope
	// it was declared in (i.e. the parent)
	public function get_parent_scope () {
		if (!$this->scope) return null;
		return $this->scope->get_super_scope();
	}
	
	// override to return the base indent of the symbol
	// before any recursion has been applied
	public function get_base_indent () {
		return 0;
	}
	
	// override and return to make symbols always
	// print block headers even in succession
	public function always_print_header () {
		return false;
	}
	
	// override to specify HEADER_SECTION for the symbol
	public function get_section () {
	}
	
	// return true if the symbol can be printed to output
	public function is_printable () {
		if (!$this->is_free()) {
			$this->ignore = $this->header->framework->ignore_type($this->name);	
		}
		
		return (!$this->ignore);
	}
	
	// override to specify if the symbol can become a constant
	// i.e. referenced by a #define
	public function can_become_constant () {
		return false;
	}
	
	// override to change how symbols are compared
	// the default implementation compares by UUID
	public function compare (Symbol $symbol) {
		return ($this->uuid == $symbol->uuid);
	}
	
	// returns true if the symbol is a "duplicate" of $symbol
	// i.e. the they share the same name (case-insensitive) but
	// are not the same symbol (uuid's don't match)
	public function is_duplicate_of (Symbol $symbol) {
		return ($this->uuid != $symbol->uuid) && (strcasecmp($this->name, $symbol->name) == 0);
	}
	
	public function find_dependent ($name) {
		foreach ($this->dependents as $symbol) {
			if ($symbol->name == $name) {
				return $symbol;
			}
		}
	}
	
	public function add_dependent (Symbol $symbol) {
		if (!$this->find_dependent($symbol->name)) {
			$symbol->header = $this->header;
			$symbol->scope = $this->scope;
			$symbol->add_dependency($this);
			$this->dependents[] = &$symbol;
			return true;
		} else {
			return false;
		}
	}
		
	public function add_dependency ($type) {
		//print("$this->name is dependent on $type\n");
		$this->dependencies[] = $type;
	}
	
	// returns the macro the symbol is within for printing
	public function get_source_level_macro () {
		if ($this->scope) {
			return $this->scope->find_super_scope(SCOPE_MACRO, true);
		}
	}
	
	public function has_dependents () {
		return (count($this->dependents) > 0);
	}

	public function has_dependencies () {
		return (count($this->dependencies) > 0);
	}
	
	// returns a string with all dependent sources
	// Scope::print_symbol_table only prints dependents
	// for top-level symbols but will not recurse into
	// private space of symbols so you must print them
	// manually in Symbol:build_source
	public function get_dependent_source ($indent) {
		$source = "";
		foreach ($this->dependents as $dependent) {
			$dependent->build_source($indent);
			$source .= $dependent->source."\n";

			// recurse into dependents
			if ($dependent->dependents) $source .= $dependent->get_dependent_source($indent);
		}
		return $source;
	}
	
	// override to print the header to $output when the symbol
	// type is printed to as macro block.
	// the default implementation will print a single line
	// with the result of get_block_header (if available)
	public function print_block_header ($indent, Output $output) {
		if ($this->get_block_header()) $output->writeln(0, $this->get_block_header());
	}
	
	// override to return the block header type
	public function get_block_header () {
		return  null;
	}
		
	// deprecated macros must be inserted after a statment but
	// before the closing ; so this method helps to insert
	// into a string with an added space
	public function insert_deprecated_macro ($terminate = false) {
		if ($this->deprecated_macro) {
			if (!$terminate) {
				return " ".trim($this->deprecated_macro, ";");
			} else {
				return " ".$this->deprecated_macro;
			}
		} else {
			return "";
		}
	}	
	
	// returns false if the symbol can be added to the scope and
	// if not issues a message (optional) and frees the symbol
	// since it should be assumed invalid at this point
	public function verify_scope_availability (Scope $scope) {
		if (SymbolTable::table()->is_identifier_declared($this->name, $this->framework, $scope)) {
			if (MESSAGE_DUPLICATE_IDENTIFIER) ErrorReporting::errors()->add_message("identifier \"$this->name\" has already been declared in the scope");
			$this->free();
			return false;
		} else {
			return true;
		}
	}
		
	// override to build the symbol source then set to $this->source
	public function build_source ($indent = 0) {
	}	
		
	public function __toString() {
		if ($this->name != "") {
			return strtolower((string)$this->name);
		} else {
			return parent::__toString();
		}
	}
	
	protected function __free() {
		parent::__free();
			
		// cache the ignore option before we free the framework
		$this->ignore = $this->header->framework->ignore_type($this->name);	
			
		unset($this->header);
		unset($this->framework);			
		
		MemoryManager::free_array($this->dependencies);
		MemoryManager::free_array($this->dependents);
	}
	
	protected function init () {
	}
	
	function __construct(Header $header) {
		$this->header = &$header;
		$this->framework = &$header->framework;
		$this->name = DEFAULT_SYMBOL_NAME;
		$this->uuid = uniqid();
		$this->namespace = new KeywordNamespace();
		$this->init();
	}

}

?>
<?php

require_once("parser_module.php");
require_once("parser_class.php");

/**
 * Category Symbol
 */

class CategorySymbol extends ClassSymbol {
	public $external_name = null;
	
	public function finalize () {
		
		// the category names has been declared so we need to make
		// it unique by prefixing the base class name
		if (SymbolTable::table()->is_symbol_declared($this)) {
			$this->external_name = $this->name;
			$this->name = $this->super_class."_".$this->name;
		}
		
		// add the category to its super class
		if ($super_class = $this->find_super_class()) {
			$super_class->add_category($this);
		}
		
	}
		
	public function build_source ($indent) {
		//$source = indent_string($indent)."type\n";
		//$indent += 1;
		
		if ($this->external_name) {
			$source .= indent_string($indent).$this->name." = ".DECLARED_CATEGORY_KEYWORD." name '".$this->external_name."' (".$this->super_class.")\n";
		} else {
			$source .= indent_string($indent).$this->name." = ".DECLARED_CATEGORY_KEYWORD." (".$this->super_class.")\n";
		}
		
		$source = $this->add_method_source($source, $indent + 1);
		$source .= indent_string($indent)."end;\n";
		
		$this->source = $source;
	}
	
	protected function __free() {

		// remove category from super class so
		// it doesn't try to protect keywords from the category
		// which is now defunct
		if (!$this->is_free()) {
			if ($super_class = $this->find_super_class()) $super_class->remove_category($this);
		}
		
		parent::__free();
	}
	
}

/**
* Category Parser
*/
class HeaderCategoryParser extends HeaderParserModule {
		
	private $pattern_category = array(	"id" => 1, 
																			"scope" => SCOPE_CATEGORY, 
																			"start" => "/@interface\s+(\w+)\s*\(\s*(\w+)\s*\)/i",
																			"end" => "/@end/i",
																			"modules" => array(	MODULE_MACRO, MODULE_METHOD, MODULE_PROPERTY,
																													MODULE_STRUCT, MODULE_ENUM, MODULE_TYPEDEF,
																													),
																			);

	function process_scope ($id, Scope $scope) {
		$category = new CategorySymbol($this->header);
		$category->name = $scope->start_results[2];
		$category->super_class = $scope->start_results[1];
				
		// add methods from the scope
		if ($symbols = $scope->find_sub_scopes(array(SCOPE_METHOD, SCOPE_PROPERTY, SCOPE_MACRO), true, false)) {
			$category->methods = $symbols;
		}		
		
		$this->symbols->add_symbol($category);
		$scope->set_symbol($category);
		
		// notify each method it was added to the category
		if ($methods = $category->find_methods()) {
			foreach ($methods as $method) {
				$method->added_to_class($category);
			}
		}
		
	}

	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_category);
	}		

}
		

?>
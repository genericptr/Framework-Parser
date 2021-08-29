<?php

require_once("parser_module.php");
require_once("symbol.php");

/**
 * Variable Symbol
 */

class VariableSymbol extends Symbol {
	public $type;
	
	public function get_section () {
		return HEADER_SECTION_EXTERNAL_SYMBOLS;
	}
	
	public function get_base_indent () {
		return 1;
	}
	
	public function get_block_header () {
		return "var";
	}
	
	public function build_source ($indent = 0) {
		$this->source = indent_string($indent)."$this->name: $this->type".$this->insert_deprecated_macro()."; ".EXTERNAL_VARIABLE_SUFFIX."\n";
	}
	
}

/**
* Variable Parser
*/
class HeaderVariableParser extends HeaderParserModule {
		
	private $pattern_variable = array(	"id" => 1, 
																			"scope" => SCOPE_VARIABLE, 
																			"pattern" => "/(\$1)\s*([a-zA-Z0-9_,*\s]+)\s*;/",
																			);
	
	function process_scope ($id, Scope $scope) {
		// print("got variable $id at $scope->start/$scope->end\n");
		// print($scope->contents."\n");
		// print_r($scope->results);
		
		$variable = new VariableSymbol($this->header);
		
		// extract name/type list from results
		extract_name_type_list($scope->results[2], $variable->list, $variable->type);
		$base_type = $variable->type;
		
		// extract name/type pair from first item in list
		// then remove it so the other names can be added as dependents
		$variable->name = $variable->list[0];
		array_shift($variable->list);
		format_name_type_pair($variable->name, $variable->type, $this->header);
		
		$variable->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
		
		// make dependents for other names in list
		foreach ($variable->list as $name) {
			$dependent = new VariableSymbol($this->header);
			$dependent->name = $name;
			$dependent->type = $base_type;
			$dependent->deprecated_macro = $variable->deprecated_macro;
			format_name_type_pair($dependent->name, $dependent->type, $this->header);
			
			$variable->add_dependent($dependent);
			
			$this->symbols->add_used_type($dependent->type);
			$this->symbols->add_symbol($dependent);
		}
		
		$this->symbols->add_used_type($variable->type);
		$this->symbols->add_symbol($variable);

		$scope->set_symbol($variable);
	}

	public function init () {
		parent::init();
		
		// add external string macros
		$this->pattern_variable["\$1"] = implode("|", $this->header->framework->external_macros);
		
		$this->add_pattern($this->pattern_variable);
	}		

}
		

?>
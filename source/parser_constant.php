<?php

require_once("parser_module.php");
require_once("symbol.php");

/**
 * Constant Symbol
 */

class ConstantSymbol extends Symbol {
	public $type;
	public $value;

	public function get_section () {
		return HEADER_SECTION_TYPES;
	}
	
	public function get_base_indent () {
		return 1;
	}
	
	public function get_block_header () {
		return "const";
	}
	
	public function build_source ($indent = 0) {
		$this->source = indent_string($indent)."$this->name = $this->value".$this->insert_deprecated_macro().";\n";
	}
	
}

/**
* Constant Parser
*/
class HeaderConstantParser extends HeaderParserModule {
		
	private $pattern_static_const = array(	"id" => 1, 
																					"scope" => SCOPE_CONST, 
																					"pattern" => "/(static)*\s+const\s+([a-zA-Z0-9_,*\s]+)\s+=\s+(.*);/",
																				);
	
	function process_scope ($id, Scope $scope) {
		// print("got constant $id at $scope->start/$scope->end in '$path'\n");
		// print($scope->contents."\n");
		// print_r($scope->results);

		$const = new ConstantSymbol($this->header);
		
		$const->value = $scope->results[3];
		$const->value = clean_constant_literal_typecast($const->value);

		// extract name/type list from results
		// type name = value
		extract_name_type_list($scope->results[2], $const->list, $const->type);

		// extract name/type pair from first item in list
		// then remove it so the other names can be added as dependents
		$const->name = $const->list[0];
		array_shift($const->list);
		format_name_type_pair($const->name, $const->type, $this->header);

		$const->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
		
		$this->symbols->add_symbol($const);
		$scope->set_symbol($const);
	}

	public function init () {
		parent::init();
		$this->add_pattern($this->pattern_static_const);
	}		

}
		

?>
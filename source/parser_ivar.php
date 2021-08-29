<?php

require_once("parser_module.php");
require_once("language_utilities.php");

/**
 * Place holder for field source
 */
class IVarBlockSymbol extends Symbol {
	private $private_bit_field_struct_count = 0;
	
	public function get_fields () {
		return $this->scope->find_sub_scopes(SCOPE_FIELD, true, true);
	}
	
	public function finalize (ClassSymbol $class) {
		
		// finalize nested records
		if ($records = $this->scope->find_sub_scopes(SCOPE_RECORD, true, true)) {
			foreach ($records as $record) {
				// use the alias name for nested ivar records
				$record->name = $record->get_preferred_alias_name();
				$record->finalize();
			}
		}
		
		// protect field names
		$fields = $this->get_fields();
		foreach ($fields as $field) {
			
			// records are not in the same namespace as the class
			// itself so don't protect these names
			if ($field->scope->is_within(SCOPE_RECORD)) {
				continue;
			}
			
			foreach ($field->names as $key => $name) {
				

				// always prefix ivars to prevents conflicts with methods
				//if (!str_has_prefix($name, IVAR_CONFLICT_PREFIX)) $name = IVAR_CONFLICT_PREFIX.$name;
				
				$class->protect_keyword($name);
				$class->namespace->add_keyword($name);
				$field->names[$key] = $name;
				$field->added_to_class($class);
			}
		}
	}
	
	public function build_source_for_scope ($indent, Scope $scope, $in_macro = false) {
		if ($symbols = $scope->find_sub_scopes(array(SCOPE_MACRO, SCOPE_FIELD, SCOPE_RECORD), true, false)) {
			
			foreach ($symbols as $symbol) {
				
				if (is_a($symbol, "MacroSymbol")) {
					if ($symbol->pair) {
						$source .= indent_string($indent).$symbol->get_start_line()."\n";
						$source .= $this->build_source_for_scope($indent, $symbol->scope, true);
						$source .= indent_string($indent).$symbol->get_end_line()."\n";
					} else {
						if ($in_macro) $source .= indent_string($indent).$symbol->get_line()."\n";
					}
				}
				
				if (is_a($symbol, "StructSymbol")) {
					$symbol->build_source($indent);
					$source .= $symbol->source;
				}
				
				if (is_a($symbol, "FieldSymbol")) {
					
					// bit field section started
					if (($symbol->bit_field) && (!$bit_fields)) {
						$bit_field_name = $this->header->get_actual_name().$this->private_bit_field_struct_count;
						
						$source .= indent_string($indent)."_anonStruct_$bit_field_name: record\n";
						$source .= indent_string($indent + 1)."case byte of\n";
						// this is the first type of the field? what about multiple types???
						$source .= indent_string($indent + 2)."0: (anonBitField_$bit_field_name: cuint);\n";
						$source .= indent_string($indent + 2)."1: (data: bitpacked record\n";
						
						$indent += 3;
						
						$bit_fields = true;
						$this->private_bit_field_struct_count += 1;
					} elseif ((!$symbol->bit_field) && ($bit_fields)) {						
						$source .= indent_string($indent - 1)."end;\n";
						$source .= indent_string($indent - 2).");\n";
					  $source .= indent_string($indent - 3)."end;\n";
						
						$indent -= 3;
						$bit_fields = false;
					}
					
					// build source
					$symbol->build_source($indent);
					$source .= $symbol->source;
				}		
				
			}
			
			// terminate open bit fields that weren't
			// closed by changing fields
			if ($bit_fields) {						
				$source .= indent_string($indent - 1)."end;\n";
				$source .= indent_string($indent - 2).");\n";
			  $source .= indent_string($indent)."end;\n";
			}
			
		}
		
		return $source;
	}
	
	public function build_source ($indent = 0) {
		$this->source = $this->build_source_for_scope($indent, $this->scope);
	}
}

/**
* Parser for instance variable block (invoked from class parser)
*
* most the work happens in the field parser however so this parser
* just defines the scope pattern and collects fields from the scope
* after parsing is complete
*/
class HeaderIVarParser extends HeaderParserModule {
		
	private $pattern_ivar = array(	"id" => 1, 
																	"scope" => SCOPE_IVAR_BLOCK, 
																	"start" => "/@interface[^@{]+\{/is",
																	"end" => "/\}/i",
																	"modules" => array(MODULE_MACRO, MODULE_CLASS_SECTION, MODULE_FIELD, MODULE_STRUCT),
																	);			
		
	public function process_scope ($id, Scope $scope) {
		$block = new IVarBlockSymbol($this->header);
		
		$scope->set_symbol($block);
	}
	
	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_ivar);
	}		
	
}
		

?>
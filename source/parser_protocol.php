<?php

require_once("parser_module.php");
require_once("parser_class.php");

/**
 * Protocol Symbol
 */
class ProtocolSymbol extends ClassSymbol {
	public $external_name;							// name of the protocol in the framework (without protocol suffix)
	public $super_protocols = array();	// array of protocol names that the protocol extends 
	
	public function get_adoptable_methods () {
		$methods = array();
	
		// get all method scopes
		$methods = array_merge($methods, $this->find_methods());
		
		// recurse into super class if a protocol of the name can be 
		// found in the symbol table
		foreach ($this->super_protocols as $protocol) {
			if ($super = SymbolTable::table()->find_symbol($protocol, "ProtocolSymbol", ANY_HEADER, $this->framework, SEARCH_IMPORTED_FRAMEWORKS)) {
				$methods = array_merge($methods, $super->get_adoptable_methods());
			}
		}
		
		return $methods;
	}
	
	public function get_section () {
		return HEADER_SECTION_PROTOCOLS;
	}
	
	public function build_source ($indent) {
		//$source = indent_string($indent)."type\n";
		//$indent += 1;
		
		if ($this->super_class) {
			$protocols = implode(", ", $this->super_protocols);
			$source .= indent_string($indent).$this->name." = ".DECLARED_PROTOCOL_KEYWORD." name '$this->external_name' ($protocols)\n";
		} else {
			$source .= indent_string($indent).$this->name." = ".DECLARED_PROTOCOL_KEYWORD." name '$this->external_name'\n";
		}
		
		$source = $this->add_method_source($source, $indent + 1, false);
		$source .= indent_string($indent)."end;\n";
		
		$this->source = $source;
	}
}

/**
 * Protocol Scope
 */

class ProtocolScope extends Scope {
	protected function added_to_scope ($scope) {
		// see ClassScope for description
		$name = trim($this->start_results[1]).PROTOCOL_SUFFIX;
		SymbolTable::table()->add_implicit_pointer($name);
	}
}

// register scope class
Scope::add_class_for_scope(SCOPE_PROTOCOL, "ProtocolScope");

/**
* Protocol Parser
*/
class HeaderProtocolParser extends HeaderParserModule {
		
	private $pattern_protocol = array(	"id" => 1, 
																			"scope" => SCOPE_PROTOCOL, 
																			"start" => "/@protocol\s+([^;<\n]+)\s*(<(.*?)>)*\s*(;)*/i",
																			"end" => "/@end/i",
																			"modules" => array(	MODULE_MACRO, MODULE_CLASS_SECTION, MODULE_STRUCT, MODULE_METHOD, MODULE_PROPERTY,
																													MODULE_STRUCT, MODULE_ENUM, MODULE_TYPEDEF),
																			);
	
	/**
	 * Methods
	 */
		
	public function accept_pattern_results ($id, $results) {
		// we have any annoying conflict with @protocol forward
		// declarations so we need to check if the protocol has
		// a semi-colon (which is a forward declaration) and reject
		// the pattern.
		if (preg_match("/;\s*$/", $results[4])) {
			return false;
		} else {
			return true;
		}
	}
		
	public function process_scope ($id, Scope $scope) {
		//print("got protocol at $scope->start/$scope->end\n");
		//print($scope->contents."\n");
		
		$protocol = new ProtocolSymbol($this->header);
		
		// build protocol array
		$protocol->external_name = trim($scope->start_results[1]);
		$protocol->name = $protocol->external_name.PROTOCOL_SUFFIX;
		
		// super protocols
		if ($scope->start_results[3]) {
			$protocols = preg_split("/\s*,\s*/", $scope->start_results[3]);
			foreach ($protocols as $name) $protocol->super_protocols[] = $name.PROTOCOL_SUFFIX;
			
			// NOTE: we can only set a single super class which is used
			// for various ClassSymbol methods but this should probably
			// allow for a variable amount of super classes to accomidate protocols 
			$protocol->super_class = $protocol->super_protocols[0];
		}
		
		// add methods from the scope
		if ($symbols = $scope->find_sub_scopes(array(SCOPE_METHOD, SCOPE_PROPERTY, SCOPE_MACRO, SCOPE_KEYWORD), true, false)) {
			$protocol->methods = $symbols;
		}		
		
		$this->symbols->add_symbol($protocol);
		$scope->set_symbol($protocol);
		
		// notify each method it was added to the protocol
		if ($methods = $protocol->find_methods()) {
			foreach ($methods as $method) {
				$method->added_to_class($protocol);
			}
		}
		
	}
		
	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_protocol);
	}		

}
		

?>
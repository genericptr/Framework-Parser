<?php

require_once("parser_header.php");
require_once("parser_method.php");
require_once("symbol.php");

/**
 * Method symbol
 */
class PropertySymbol extends MethodSymbol {
	public $type;
	public $remote_messaging_modifier;	
	public $garbage_collector_hint;
	public $list = array();
	
	public $setter = null;
	public $getter = null;
	private $attributes = array();
	
	public function set_getter ($name) {
		$this->getter = $name;
	}
	
	public function set_setter ($name) {
		$this->setter = $name;
	}
	
	public function add_attribute ($attribute) {
		$this->attributes[] = strtolower($attribute);
	}
	
	public function is_read_only () {
		return in_array("readonly", $this->attributes);
	}
	
	public function get_getter () {
		if ($this->getter) {
			return $this->getter;
		} else {
			return $this->name;
		}
	}
	
	public function get_setter () {
		if ($this->setter) {
			return $this->setter;
		} else {
			return "set".ucwords($this->name);
		}
	}
	
	public function added_to_class (ClassSymbol $class) {
	}
	
	public function finalize (ClassSymbol $class) {
		
		// properties only need to be protected in the current class namespace
		// because the selectors are always the same even for duplicates in
		// super classes, which are permitted to overwrite in external objcclass's
		
		// protect getter
		$getter = $this->get_getter();
		//$class->protect_keyword($getter);		
		$class->namespace->protect_keyword($getter);
		$class->namespace->add_keyword($getter);
		global_namespace_protect_keyword($getter);
		$this->getter = $getter;

		// protect setter
		if (!$this->is_read_only()) {
			$setter = $this->get_setter();
			//$class->protect_keyword($setter);
			$class->namespace->protect_keyword($setter);		
			$class->namespace->add_keyword($setter);
			global_namespace_protect_keyword($setter);
			$this->setter = $setter;
		}
		
		// finalize depedents
		foreach ($this->dependents as $property) {
			$property->finalize($class);
		}
	}
	
	public function build_source ($indent = 0) {
		
		// add setter
		if (!$this->is_read_only()) {
			$name = $this->get_setter();
			$message = trim($name, KEYWORD_PROTECTION_SUFFIX);
			$type = $this->type;
			$source .= indent_string($indent)."procedure $name(newValue: $type); message '$message:';";
			if ($this->deprecated_macro) $source .= " ".$this->deprecated_macro;
			$source .= "\n";
		}

		// add getter
		$name = $this->get_getter();
		$message = trim($name, KEYWORD_PROTECTION_SUFFIX);
		$type = $this->type;
		$source .= indent_string($indent)."function $name: $type; message '$message';";
		if ($this->deprecated_macro) $source .= " ".$this->deprecated_macro;
		//$source .= "\n";
		
		// add dependents
		if (count($this->dependents) > 0) {
			$source .= "\n";
			$source .= $this->get_dependent_source($indent);
			$source = trim($source, "\n");
		}
		
		$this->source = $source."\n";
	}
		
}


/**
* Property parser
*/
class HeaderPropertyParser extends HeaderParserModule {
		
	private $pattern_property = array(	"id" => 1, 
																			"scope" => SCOPE_PROPERTY, 
																			"pattern" => "/@property\s*(\((.*?)\))*\s*(.*?);/is",
																			);
	
	// the property is a function pointer with potential
	// nested function pointer parameters
	//
	// NSUInteger (*hashFunction)(const void *item, NSUInteger (*size)(const void *item))
	// void (^terminationHandler)(NSTask *);
	private $pregex_function_pointer = "/(.*?)\s*\(\s*([\^*]+)\s*(\w+)\s*\)\s*\((.*)\)/";
	
	private function process_property_attributes (PropertySymbol $property, $attributes) {
		$parts = explode(",", $attributes);
		
		foreach ($parts as $part) {
			$part = trim($part);
			
			// split by = to get assigned accessor names
			if (preg_match("/(\w+)\s*=\s*(\w+)/", $part, $captures)) {

				if ($captures[1] == strtolower("getter")) $property->set_getter($captures[2]);
				if ($captures[1] == strtolower("setter")) $property->set_setter($captures[2]);
				
			} else {
				// add attribute to property for later use
				$property->add_attribute($part);
			}
		}
	}
	
	private function process_property_name_type (PropertySymbol $property, $contents) {
		$contents = replace_remote_messaging_modifiers($contents, $property->remote_messaging_modifier);	
		$contents = replace_garbage_collector_hints($contents, $property->garbage_collector_hint);
		
		// separate conforms to hints from property name so the identifiers don't
		// get merged in extract_name_type_list
		// example: id<AVPlayerItemOutputPullDelegate>delegate;
		$contents = preg_replace("/(\w+\s*<[^>]+>)(\w+)/", " $1 $2" , $contents);
		
		// extract property name list and type from contents
		extract_name_type_list($contents, $property->list, $property->type);
		
		// use the first name as the base then remove it
		// so it's not added again
		$property->name = $property->list[0];
		array_shift($property->list);
		format_name_type_pair($property->name, $property->type, $this->header);
	}
										
	function process_scope ($id, Scope $scope) {
		//print("got property at $scope->start/$scope->end\n");
		//print($scope->contents."\n");
		//print_r($scope->results);
		
		$property = new PropertySymbol($this->header);
				
		if (preg_match($this->pregex_function_pointer, $scope->results[3], $captures)) {
			
			// get the name and parse attributes
			$property->name = $captures[3];
			$property->selector = $captures[3];
			$this->process_property_attributes($property, $scope->results[3]);
			
			if ($captures[2] == "*") {
				// build the function pointer from inline pointer type
				$function_pointer = HeaderFunctionParser::build_function_pointer($this->header, $captures[1], null, $captures[4], FUNCTION_SOURCE_TYPE_TYPE);

				// add the function pointer as a callback
				$callback_name = ucwords($property->name);
				$property->type = HeaderFunctionParser::add_callback($this->header, $callback_name, $function_pointer);
			}
			
			// the function pointer is a block
			if ($captures[2] == "^") {
				$property->type = OPAQUE_BLOCK_TYPE;
			}
			
		} else {
			$this->process_property_attributes($property, $scope->results[2]);
			$this->process_property_name_type($property, $scope->results[3]);
		}
		
		// add additional properties from names list
		// as dependents of the base property
		foreach ($property->list as $name) {
			$dependent = clone $property;
			$dependent->name = $name;
			$dependent->dependents = array();
			
			format_name_type_pair($dependent->name, $dependent->type, $this->header);

			$property->add_dependent($dependent);
		}
		
		// add the base property to the scope
		$scope->set_symbol($property);
	}
	
	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_property);
	}		

}
		

?>
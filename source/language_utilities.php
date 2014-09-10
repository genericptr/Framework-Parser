<?php

require_once("settings.php");

/**
 * Global Variables
 */
		
// pascal keywords to protect
$reserved_keywords = array( "const", "object", "string", "array", "var", "set", "interface", "unit", "begin", "end",
														"type", "raise", "property", "to", "for", "with", "function", "procedure", "result", "self",
														"pointer", "create", "new", "dispose", "label", "packed", "record", "class", "implementation",
														"program", "file", "message",
														);

// hints to the Objective-C garbage collector which are removed
// unless supports the garbage collector
$garbage_collector_hints = array("__strong", "__weak", "volatile", "___strong", "___weak");

// integer signs for c types that we need to remove as a step before
// formatting types to pascal
$c_integer_signs = array("unsigned", "signed");

// these identifiers are used with remote messaging
$remote_messaging_modifiers = array("oneway", "in", "out", "inout", "bycopy", "byref");

// unused keywords that should be removed as last resort
// these keywords are implicit in pascal so they're not needed along with the primary identifier
$unused_c_keywords = array ("const", "struct", "enum", "union");

// standard C/Objective-C types which are known as declared in all frameworks
$standard_c_types = array(	"id", "SEL", "IMP", "unichar", "double",
														);

/**
 * Replacing Utilities
 */

// master function for formatting a any raw-c type appearing in source
// to a pascal equivalent.
//
// for example the field "void *somePtr[10];" should be split then pass only the type section
// i.e. "void *" into format_c_type.
// 
// notes:
// 1) leaving * characters at the end of the type will encode the 
//    to type as a pointer using defined pointer types in $pointer_types 
//    or by appending a generic pointer suffix.
// 2) all classes if parsed are implicit pointers so types like NSObject*
//    will not be converted to a pointer.
// 3) all framework replace types (defined in frameworks.xml) will be converted.
// 4) static types from $replace_types will be replaced.
// 5) struct and const keywords will be removed because they are implicit
//    in the case of struct you need to be parse this beforehand  if you want
//    to retain any information about why it was used
// 6) array brackets will be removed (name[2]) so you should call format_array_type to retain these
//    values beforehand
// 7) protocol hints (id <NSObject>) are removed so you should call replace_conforms_to_hint
//    to retain these values beforehand
function format_c_type ($type, Header $header) {
	$type = trim($type);
	
	// all these types are unused in pascal so must be removed
	// if they are needed prior to calling this function they
	// must be called then to prevent loosing any meta data
	$type = replace_remote_messaging_modifiers($type, $null);	
	$type = replace_garbage_collector_hints($type, $null);
	$type = replace_unused_keywords($type);
	
	$type = trim($type);
	
	// capture pointer modifiers at the end of the type
	if (preg_match("/^(.*?)\s*([* ]+)$/", $type, $captures)) {
		$type = trim($captures[1]);
		$pointer_mods = str_remove_white_space($captures[2]);
	} else {
		$pointer_mods = null;
	}
		
	// replace against framework defined replace types
	$type = $header->framework->replace_type($type);
	
	// replace protocol conform-to hints (id <NSObject>)
	// with protocol name (NSObjectProtocol)
	$type = replace_conforms_to_hint($type, $null);
	
	// replace integer signing keywords which
	// should have been translated into the type prior
	// to reaching this step
	$type = replace_integer_signs($type, $null);
	
	// replace pointer types
	if ($pointer_mods) {

		// if the type is not an implicit point attempt to convert
		// to pointer for each modifier
		if (!SymbolTable::table()->is_type_implicit_pointer($type)) {
			for ($i=0; $i < strlen($pointer_mods); $i++) { 
				$type = replace_pointer_type($type, $header);
			}
		} else {
			// if there are 2 pointer modifiers force even implicit pointers
			// as pointers (such as NSError**)

			// NOTE: we may need an option to make "forceable" implied pointers like
			// like classes (NSError **) since certain types like "pointer" and CFStringPtr
			// never should be suffixed unless a bad type is to be made
			if (strlen($pointer_mods) == 2) $type = $type.POINTER_SUFFIX;
		}

	}
		
	// attempt to replace type against after pointer modifications
	$type = $header->framework->replace_type($type);
		
	// remove array brackets
	$type = preg_replace("/(\w+)\s*\[\s*\w*\s*\]/", "$1", $type);
	$type = trim($type);
	
	// remove bit field
	$type = preg_replace("/(\w+)\s*:\s*(\d+)/", "$1", $type);
	$type = trim($type);
	
	// return formatted type
	return $type;
}

// Replace type with pointer equivalent
// $type = the type string to replace
// $header = reference to header class
function replace_pointer_type ($type, Header $header) {
	$found = false;
	$type = trim($type);	
	
	// type is an implicit pointer, don't replace
	if (SymbolTable::table()->is_type_implicit_pointer($type)) return $type;	
	
	// the type is already a defined pointer, don't replace
	if ($header->framework->is_type_defined_pointer($type)) return $type;
		
	// use preferred pointer type
	foreach ($header->framework->pointer_types as $objc_type => $replace_type) {
		if (preg_match("/^$objc_type$/i", $type)) {
			$found = true;
			$type = $replace_type;
			break;
		}
	}
	
	// use generic pointer type
	if (!$found) $type = $type.POINTER_SUFFIX;
	
	return $type;
}

// reverts a type that was encoded as a generic pointer in replace_pointer_type()
// or format_c_type() back to a plain type by trimming the pointer suffix 
function revert_generic_pointer (&$type) {
	$type = preg_replace("/".POINTER_SUFFIX."$/", "", $type);
	return $type;
}

// removes c integer signs in strings
function replace_integer_signs ($string, &$io_hint) {
	global $c_integer_signs;
	$io_hint = false;
	
	foreach ($c_integer_signs as $type) {
		$out_string = istr_replace_word($type, "", $string);
		if ($out_string != $string) {
			$io_hint = $type;
			$string = $out_string;
		}
	}
	
	return $string;
}

// replace protocol which type conforms to (id <NSURLHandleClient>)
// if there is a list of protocols then use the base type and ignore
// since we can only support a single type in objective pascal
function replace_conforms_to_hint ($string, &$io_hint) {
	$io_hint = null;
	if (preg_match("/(\w+)+\s*<([^>]+)>/", $string, $captures)) {
		
		$parts = preg_split("/\s*,\s*/", trim($captures[2]));
		if (count($parts) == 1) {
			$type = $parts[0].PROTOCOL_SUFFIX;
		} else {
			$type = $captures[1];
		}
		
		$string = preg_replace("/(\w+)+\s*<([^>]+)>/", $type, $string);
		$io_hint = $captures[2];
	}
	
	return $string;
}

// Replace objc type with preferred type
// if a header is supplied it will also search framework specific
// types but this parameter is optional
	
function replace_garbage_collector_hints ($string, &$io_hint) {
	global $garbage_collector_hints;
	$io_hint = false;
	
	foreach ($garbage_collector_hints as $hint) {
		$out_string = str_ireplace($hint, "", $string);
		if ($out_string != $string) {
			$io_hint = $hint;
			$string = $out_string;
		}
	}
	
	return $string;
}

function replace_remote_messaging_modifiers ($string, &$io_modifier) {
	global $remote_messaging_modifiers;
	$io_hint = false;

	foreach ($remote_messaging_modifiers as $modifier) {
		$out_string = preg_replace("!\b".$modifier."\b!", "", $string);
		if ($out_string != $string) {
			$io_modifier = $modifier;
			$string = $out_string;
		}
	}
		
	return trim($string);
}

function replace_unused_keywords ($string) {
	global $unused_c_keywords;
	
	foreach ($unused_c_keywords as $keyword) {
		$string = istr_replace_word($keyword, "", $string);
	}
	
	return trim($string);
}

function replace_array_brackets ($string) {
	return preg_replace_all("/(\[\s*\w+\s*\])/", "", $string);
}

// utility to further process each name/type in $list
// returned from extract_name_type_list
function format_name_type_pair (&$name, &$type, Header $header, $format_name = false) {
	if (preg_match("/^\*/", $name)) {
		$name = trim($name, "*");
		$type = format_c_type("$type*", $header);
	} else {
		$type = format_c_type($type, $header);
	}
	
	if ($format_name) $name = format_c_type($name, $header);
}

// parses a comma-separated list of names and types
// the names in list may contain * pointer modifiers
// so you should call format_name_type_pair to format the
// name and type to pascal-safe values
// 
// example: NSString *fromValue, toValue, byValue;
function extract_name_type_list ($contents, &$list, &$type) {
	$contents = trim($contents);
	
	// remove unused keywords of all types
	$contents = replace_remote_messaging_modifiers($contents, $null);	
	$contents = replace_garbage_collector_hints($contents, $null);
	$contents = replace_conforms_to_hint($contents, $null);
	$contents = replace_unused_keywords($contents);
	
	// move [] directly after word
	$contents = preg_replace("/(\w+)\s*\[\s*(\w+)*\s*\]/", " $1[$2]" , $contents);
	
	// move : directly after word
	$contents = preg_replace("/(\w+)\s*:/", " $1:" , $contents);
	
	// move * in pointers directly before the word
	$contents = preg_replace("/\*{1}\s*(\w+)/", " *$1" , $contents);
	$contents = trim($contents);
	
	// move , in type lists directly after the word
	$contents = preg_replace("/(\*)*(\w+)\s*,{1}/", "$1$2, ", $contents);
	
	// split the contents into parts divided by white space
	$parts = preg_split("/\s+/", $contents);

	// the name is the last indentifier
	$last_identifier = array_pop($parts);
	
	// pop off additional names in list
	foreach ($parts as $key => $value) {
		if (preg_match("/([*]*\w+),$/", $value, $captures)) {
			$list[] = $captures[1];
			unset($parts[$key]);
		}
	}
	$list[] = $last_identifier;
	//print_r($list);
	
	// rebuild the type from the remaining parts
	$type = implode(" ", $parts);
	
	// what could go wrong?
	return true;
}

// parses a single c name-type pair into 2 parts
// you should format_c_type() on the returned $type
// as it may contain pointer references (*)
//
// example: unsigned long long *myVar
//					myVar: uclonglongptr;
function extract_name_type_pair ($pair, &$name, &$type) {
	$pair = trim($pair);
	
	// remove unused keywords of all types
	$pair = replace_remote_messaging_modifiers($pair, $null);	
	$pair = replace_garbage_collector_hints($pair, $null);
	$pair = replace_conforms_to_hint($pair, $null);
	$pair = replace_unused_keywords($pair);
	
	// move * in pointers directly after the word to make splitting easier
	$pair = preg_replace("/(\w+)\s*([*]+)/", " $1$2 " , $pair);
	$pair = trim($pair);
	
	// split by spaces
	$parts = preg_split("/\s+/", $pair);

	if (count($parts) > 1) {
		// pop off the last part which is the name
		$name = array_pop($parts);

		// join the remaining parts which is the type
		$type = implode(" ", $parts);
		return true;
	} else {
		return false;
	}
}

function convert_integer_value ($value) {
	$value = trim($value);

	// hexadecimal
	if (preg_match("/^0x([a-z0-9]+)$/i", $value)) {
		$value = preg_replace("/^0x/i", "$", $value);
		$value = preg_replace("/(ULL|UL|LL|U|L)$/i", "", $value);
	}

	// floating point hint
	$value = preg_replace("/^(\d+.\d+)f$/i", "$1", $value);
	
	// floating point error
	// NOTE: what really is this value?
	$value = preg_replace("/^([0-9.]+)E[-+]*38F$/i", "$1", $value);
	
	// integer hint
	if (preg_match("/^-{0,1}\d+\w+$/", $value)) $value = preg_replace("/(ULL|UL|U|L)$/i", "", $value);
		
	// ~ not
	$value = preg_replace("/^~\s*(-{0,1}\d+|\$[a-z0-9]+)/", "not($1)", $value);

	return $value;
}

function convert_single_value ($value) {
	
	// remove optional parenthesis
	$value = str_replace("(", "", $value);
	$value = str_replace(")", "", $value);

	// shl
	if (preg_match("/(\w+)\s*<<\s*(\w+)/", $value, $captures)) {
		
		// convert the integers
		$left_part = convert_integer_value($captures[1]);
		$right_part = convert_integer_value($captures[2]);

		$value = "$left_part shl $right_part";			
	} else {
		$value = convert_integer_value($value);
	}
	
	return $value;
}

// converts an "assigned" c value (such as in #define for enum {} block )
// and adds an values to the symbol table as used (SymbolTable::add_used_type)
// note: the returned value may be an array of values (for bitwise operators)
function convert_assigned_value ($value, &$bitwise_or) {

	// remove type hints before type
	// for example: (unsigned long) -1
	$value = preg_replace("/\(([a-z0-9\s_]+)\)\s*([^,)]+)/i", "$2", $value);
	
	// remove optional parenthesis
	$value = str_replace("(", "", $value);
	$value = str_replace(")", "", $value);
	
	// replace Objective-C NSStrings with pascal string
	$value = preg_replace("/@\"(.*?)\"/", "'$1'", $value);
	
	// replace c-strings with pascal string
	$value = preg_replace("/\"(.*?)\"/", "'$1'", $value);
		
	// bitwise or list of values
	if ($values = preg_split("/\s*\|\s*/", $value)) {
		if (count($values) > 1) {
			$clean_values = array();
				
			foreach ($values as $value) {
				$value = trim($value);
				$value = convert_single_value($value);
				
				// wrap values with space in parenthesis
				if (preg_match("/\s+/", $value)) $value = "($value)";
				
				$clean_values[] = $value;
				SymbolTable::table()->add_used_type($value);
			}

			$bitwise_or = true;
			return $clean_values;
		}
	}
	
	// single value to convert
	$value = trim($value);
	$value = convert_single_value($value);
	SymbolTable::table()->add_used_type($value);

	return $value;
}

// returns true if the value (strings only) is valid pascal
// will not process concatenated values (expressions), like "1 + 2 + 3" properly
function is_pascal_value_format ($value) {
	
	// only accept string!
	if (!is_string($value)) return false;
	
	// string
	if (preg_match("/^'.*?'?/", $value)) return true;
	
	// integer
	if (preg_match("/^[-]*\d+(\.\d+)*$/", $value)) return true;
	
	// hexadecimal
	if (preg_match("/^[$]\w+$/", $value)) return true;
	
	// shl
	if (preg_match("/^\d+\s*shl\s*\d+$/", $value)) return true;
	
	// single-word identifier
	if (preg_match("/^\w+$/", $value)) return true;
	
}

// builds an array of elements and type into 
// pascal source like array[0..1] of type
function build_array_elements_source ($elements, $type) {
	$source .= "array[";
	foreach ($elements as $element) {
		if (preg_match("/[a-z_]+/i", $element)) {
			$source .= "0..($element)-1, ";
		} else {
			$count = $element - 1;
			if ($count < 0) $count = 0;
			$source .= "0..$count, ";
		}
	}
	$source = trim($source, ", ");
	$source .= "] of $type";
	return $source;
}

// returns an array of array elements for a string
function find_array_elements ($string, &$elements) {
	$elements = array();
	
	if (preg_match_all("/\[\s*(\w+)\s*\]/", $string, $captures)) {
		
		// add elements
		foreach ($captures[1] as $element) $elements[] = $element;
		
		return true;
	} else {
		return false;
	}	
}

function format_array_type (&$type, &$typedef, $name_prefix, Header $header) {
	$type_array_pattern = "/(\w+)\s*\[\s*(\d)*\s*\]/";
	$typedef = null;
	
	// WARNING: this will fail for multi-dimensional arrays!
	// we need to fix this like in find_array_elements()
	
	if (preg_match($type_array_pattern, $type, $captures)) {
		$elements = (int)$captures[2];
		$type = preg_replace($type_array_pattern, "$1", $type);
		
		// ??? we nee to add a supporting pointer type also with the array
		/*
		$inline_array = new InlineArrayTypedefSymbol($header, $elements);
		$inline_array->type = format_c_type($type, $header);
		$inline_array->name = "Inline".$name_prefix.ucfirst($inline_array->type).INLINE_ARRAY_SUFFIX;
		
		if (!SymbolTable::table()->is_type_declared($inline_array->name)) {
			SymbolTable::table()->add_symbol($inline_array);
			SymbolTable::table()->add_implicit_pointer($inline_array->name);
		}
		*/
		
		$type_plain = format_c_type($type, $header);
		$type = format_c_type("$type*", $header);
		if ($elements > 0) {
			$type .= " { $elements element array of ".$type_plain." }";
		} else {
			$type .= " { variable size array of ".$type_plain." }";
		}
	
		return true;
	} else {
		return false;
	}
}

function format_array_pair (&$name, &$type, &$typedef, $name_prefix, Header $header) {
	$type_array_pattern = "/(\w+)\s*\[\s*(\d)*\s*\]/";
	$typedef = null;
	
	if (preg_match($type_array_pattern, $name, $captures)) {
		$elements = (int)$captures[2];
		
		$name = preg_replace($type_array_pattern, "$1", $name);
		
		$type_plain = format_c_type($type, $header);
		$type = format_c_type("$type*", $header);
		if ($elements > 0) {
			$type .= " { $elements element array of ".$type_plain." }";
		} else {
			$type .= " { variable size array of ".$type_plain." }";
		}
				
		return true;
	} else {
		return false;
	}
}

/**
 * General Utilities
 */

function indent_string ($level) {
	for ($i=0; $i < $level; $i++) $indent .= SOURCE_INDENT;
	return $indent;
}

function is_type_external_macro ($type, Header $header) {
	return in_array($type, $header->framework->external_macros);
}

function is_keyword_reserved ($keyword) {
	global $reserved_keywords;
	if (in_array(strtolower($keyword), $reserved_keywords)) return true;
}

/**
 * Keyword Protection
 */

// protect in reserved keyword namespace
function reserved_namespace_protect_keyword (&$keyword) {
	while (is_keyword_reserved($keyword)) protect_keyword($keyword);
	return $keyword;
}

// protects a keyword in the global namespace which includes
// reserved namespace and declared symbol types
function global_namespace_protect_keyword (&$keyword) {
	
	// protect in reserved namespace
	reserved_namespace_protect_keyword($keyword);
	
	// protect in global declared symbols
	while (SymbolTable::table()->is_type_declared($keyword)) protect_keyword($keyword);
	
	return $keyword;
}

// "protects" a keyword by suffixing
// this is the default method to protect keywords across the entire parser
function protect_keyword (&$keyword) {
	$keyword .= KEYWORD_PROTECTION_SUFFIX;
	return $keyword;
}

function unprotect_keyword (&$keyword) {
	$keyword = rtrim($keyword, KEYWORD_PROTECTION_SUFFIX);
	return $keyword;
}

?>
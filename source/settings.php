<?php

/**
 * Global command line options
 */

$command_line_options = array();

function is_parser_option_enabled ($option) {
	global $command_line_options;
	return (bool)($command_line_options[$option] != null);
}

function get_parser_option ($option) {
	global $command_line_options;
	return $command_line_options[$option];
}

function get_parser_option_array ($option) {
	global $command_line_options;
	return preg_split("/\s*,\s*/", $command_line_options);
}

function set_parser_option ($option, $value) {
	global $command_line_options;
	$command_line_options[$option] = $value;
}

function get_parser_option_path ($option) {
	global $command_line_options;
	return expand_path($command_line_options[$option]);
}

function clear_parser_option ($option) {
	global $command_line_options;
	unset($command_line_options[$option]);
}

// command line options
define("PARSER_OPTION_OUTPUT", "out");
define("PARSER_OPTION_SHOW", "show");
define("PARSER_OPTION_ALL", "all");
define("PARSER_OPTION_HEADER", "header");																// parses a single header
define("PARSER_OPTION_DIRECTORY", "dir");																// parses all headers in the directory as framework
define("PARSER_OPTION_BATCH", "batch");																	// parses all frameworks in the batch directory specified
define("PARSER_OPTION_GROUP", "group");																	// groups all frameworks together into a single unit (like MacOSAll)
define("PARSER_OPTION_GROUP_FRAMEWORKS", "group_frameworks");						// sets the list of frameworks to add to the group unit (when -group is enabled)
define("PARSER_OPTION_FRAMEWORKS", "frameworks");												// explicitly sets the list of frameworks to parse (when -batch isn't enabled)
define("PARSER_OPTION_SEARCH_PATHS", "search_paths");										// adds additional search paths when looking for frameworks
define("PARSER_OPTION_SDK", "sdk");																			// sdk for default frameworks directory
define("PARSER_OPTION_AUTOLOAD_IMPORTED_FRAMEWORKS", "autoload_imported_frameworks"); 
define("PARSER_OPTION_ONLY", "only");																		// only print the list of frameworks or headers
define("PARSER_OPTION_IGNORE", "ignore");																// ignore the list of frameworks or headers
define("PARSER_OPTION_DRY_RUN", "dryrun");															// parses but doesn't produce any files
define("PARSER_OPTION_BUILD_SKELETONS", "build_skeletons");							// builds framework skeletons
define("PARSER_OPTION_BUILD_COMMANDS", "build_commands");								// specify i386, arm or ppc as value
define("PARSER_OPTION_OPAQUE_TYPES", "opaque_types");										// UNDER DEVELOPMENT: prints a unit containing all opaque types found
define("PARSER_OPTION_CLASS_DEFINITIONS", "class_definitions");					// prints global class definitions (should be enabled)
define("PARSER_OPTION_FRAMEWORKS_XML", "frameworks_xml");								// loads additional framework definition XML files
define("PARSER_OPTION_CLEAN", "clean");																	// deletes all parser made files from the output directory (used for sharing skeletons)
define("PARSER_OPTION_DEFAULT_FRAMWORK", "default_framework");					// sets the default framework to parse against when using: -batch -frameworks and -header 
define("PARSER_OPTION_TEMPLATE", "template");														// sets the template for root or group units
define("PARSER_OPTION_PATCH", "patch");																	// applies a .patch file (must be created manually) to the output directory after being parsed
define("PARSER_OPTION_SKELETON", "skeleton");														// defines the skeleton directory to copy to the output directory (see /skeletons)
define("PARSER_OPTION_UMBRELLA", "umbrella");														// UNDER DEVELOPMENT: when parsing a single framework explicitly sets the umbrella unit
define("PARSER_OPTION_COMMAND", "command");															// loads a options from a command file (see /commands directory)
define("PARSER_OPTION_UNIT", "unit");																		// when -header is enabled -unit will print a .pas unit instead of a .inc include file
define("PARSER_OPTION_ALL_UNITS", "all_units");													// always print a unit for each framework even when -group is enabled
define("PARSER_OPTION_XCODE", "xcode");																	// defines the location of Xcode.app which contains SDK's since version 4.3
define("PARSER_OPTION_MACOS", "macos");																	// helper to load the macos.xml framework definition													
define("PARSER_OPTION_IOS", "ios");																			// helper to load the ios.xml framework definition
define("PARSER_OPTION_USES", "uses");																		// *** DEPRECATED *** imports <uses> directly from a framework definition
define("PARSER_OPTION_EXTERNC", "externc");															// enables pre-parsing extern "c" {} blocks

// options which can be passed when specifying
// frameworks with the -all switch
define("FRAMEWORK_OPTION_DISABLE_PRINTING", "^");
define("FRAMEWORK_OPTION_DISABLE_FINALIZING", "#");

define("FRAMEWORK_BASE_SEPARATOR", ":");

// group frameworks options that affect the frameworks passed with -group_frameworks
define("GROUP_FRAMEWORK_OPTION_USES", "+");							// the framework is added to the uses only
define("GROUP_FRAMEWORK_OPTION_INDEPENDENT", "^");			// the framework is not dependant on the group unit 

// macros which can be used in frameworks.xml to reference other 
// entries in the xml or command line options
define("FRAMEWORK_XML_MACRO_NAME", "%%NAME%%");											// value of <name>
define("FRAMEWORK_XML_MACRO_NAME_LOWER_CASE", "%%LC_NAME%%");				// value of <name> in lowercase
define("FRAMEWORK_XML_MACRO_NAME_UPPER_CASE", "%%UC_NAME%%");				// value of <name> in uppercase
define("FRAMEWORK_XML_MACRO_NAME_ABBREVIATION", "%%ABBRV_NAME%%");	// value of <name> in abbreviated form 
																																		// the first 2 uppercase characters are used: UIKit => UI, CoreFoundation => CF
define("FRAMEWORK_XML_MACRO_NAMES_PREGEX", "%%PREGEX_NAMES%%");			// perl-compatible regex pattern value for uppercase and abbreviated name like (UI|UIKit)+
define("FRAMEWORK_XML_MACRO_SDK", "%%SDK%%");												// value of -sdk switch
define("FRAMEWORK_XML_MACRO_XCODE", "%%XCODE%%");										// value of -xcode switch (path to Xcode.app)

// name of the base framework.xml entry
define("BASE_FRAMEWORK", "base");

/**
 * Framework Directories
 */

// default location of Xcode if not specified with -xcode
// this is required to find SDK's on systems with Xcode 4.3 or newer
$default_xcode_path = "/Applications/Xcode.app";

// all system defined directories which contain frameworks
$system_framework_directories = array(	
	
																				// as of Xcode 4.3 SDK's are stored in the application bundle
																				"%%XCODE%%/Contents/Developer/Platforms/MacOSX.platform/Developer/SDKs/%%SDK%%/System/Library/Frameworks",
																				"%%XCODE%%/Contents/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/%%SDK%%/System/Library/Frameworks",
																				
																				// legacy SDK locations:
																				"/Developer/SDKs/%%SDK%%/System/Library/Frameworks",
																				"/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/%%SDK%%/System/Library/Frameworks",
																				);

// returns the full path for an sdk by searching
// the availble list of system framework directories
function framework_directory_for_sdk ($sdk, $xcode) {
	global $system_framework_directories;
	global $default_xcode_path;
	
	// use the default xcode path if not specified in the command
	if (!$xcode) $xcode = $default_xcode_path;
	
	foreach ($system_framework_directories as $path) {
		$path = str_replace(FRAMEWORK_XML_MACRO_SDK, $sdk, $path);
		$path = str_replace(FRAMEWORK_XML_MACRO_XCODE, $xcode, $path);
		//print("  searching: $path\n");
		if (file_exists($path)) return $path;
	}
}

/**
 * Framework XML Definitions
 */

// @ is replaced with the directory of parser.php

$framework_xml_definitions = array(	"@/defs/base.xml",
																		"@/defs/universal.xml"
																	);

function get_framework_definitions () {
	global $framework_xml_definitions;
	return $framework_xml_definitions;
}

/**
 * Scopes
 */
define("SCOPE_ANY", "*");
define("SCOPE_NULL", "?");
define("SCOPE_MACRO", "pascal.macro");
define("SCOPE_SOURCE", "pascal.source");
define("SCOPE_CLASS", "pascal.class");
define("SCOPE_CATEGORY", "pascal.class.category");		// conforms to SCOPE_CLASS
define("SCOPE_PROTOCOL", "pascal.class.protocol");		// conforms to SCOPE_CLASS
define("SCOPE_RECORD", "pascal.record");
define("SCOPE_METHOD", "pascal.method");
define("SCOPE_PROPERTY", "pascal.method.property");		// conforms to SCOPE_METHOD
define("SCOPE_KEYWORD", "pascal.keyword");
define("SCOPE_BLOCK", "pascal.block");
define("SCOPE_BLOCK_CONST", "pascal.block.const");		// conforms to SCOPE_BLOCK
define("SCOPE_IVAR_BLOCK", "pascal.block.ivar");			// conforms to SCOPE_BLOCK
define("SCOPE_FIELD", "pascal.field");
define("SCOPE_FIELD_CONST", "pascal.field.const");		// conforms to SCOPE_FIELD
define("SCOPE_TYPE", "pascal.type");
define("SCOPE_FUNCTION", "pascal.function");
define("SCOPE_FUNCTION_INLINE", "pascal.function.inline");		// conforms to SCOPE_FUNCTION
define("SCOPE_VARIABLE", "pascal.variable");
define("SCOPE_CONST", "pascal.const");
define("SCOPE_FORWARD", "pascal.class.forward");

/**
 * Header Sections
 */

// these macros appear in the base unit and correspond
// with symbols in the headers
define("HEADER_SECTION_TYPES", "TYPES");
define("HEADER_SECTION_RECORDS", "RECORDS");
define("HEADER_SECTION_PROTOCOLS", "PROTOCOLS");
define("HEADER_SECTION_CLASSES", "CLASSES");
define("HEADER_SECTION_FUNCTIONS", "FUNCTIONS");
define("HEADER_SECTION_EXTERNAL_SYMBOLS", "EXTERNAL_SYMBOLS");
define("HEADER_SECTION_INTERFACE", "INTERFACE");
define("HEADER_SECTION_IMPLEMENTATION", "IMPLEMENTATION");

/**
 * Pattern keys
 */

// patterns may also include $1 keys which are placeholders to values
// for example in the pattern ($1)+\s*($2) if you include keys of $1 and $2
// there values will be inserted into the pattern.
// this feature always you to set dynamic variables into patterns easily.

// be careful not to add patterns that start/end with generic characters
// like \s+ [\n]+ or \w+ which can seriously impact performance is used wrong 

// Public
define("PATTERN_KEY_ID", "id");
define("PATTERN_KEY_SCOPE", "scope");
define("PATTERN_KEY_PATTERN", "pattern");
define("PATTERN_KEY_START", "start");
define("PATTERN_KEY_END", "end");

// the break pattern will reject a pattern by matching
// the results from the pattern and if valid the pattern
// will not be processed.
define("PATTERN_KEY_BREAK", "break");											

// sub modules to apply to ranged patterns																													
define("PATTERN_KEY_MODULES", "modules");

// the pattern will start from the location of the last pattern 
// without appending the length
define("PATTERN_KEY_LOCATION_OFFSET", "location-offset");
																													
// for ranged patterns the end pattern will set the offset
// to the starting offset it started from to prevent
// sub-patterns from consuming the end pattern
define("PATTERN_KEY_TERMINATE_FROM_START", "terminate-from-start");

// Private
define("PATTERN_KEY_MODULE", "module"); 									// cross-reference to the module which owns the pattern
define("PATTERN_KEY_IDENTIFIER", "identifier"); 					// unique identifier of the pattern

/**
 * Modules
 */

define("MODULE_SUPER_SCOPE", "^");												// special meta-value which specifies the pattern should inherit from the super scope
define("MODULE_CLASS", "class");
define("MODULE_CLASS_SECTION", "class.section");
define("MODULE_CLASS_FORWARD", "class.forward");
define("MODULE_PROTOCOL", "protocol");
define("MODULE_CATEGORY", "category");
define("MODULE_DEFINE", "define");
define("MODULE_ENUM", "enum");
define("MODULE_FIELD", "field");
define("MODULE_FIELD_ENUM", "field.enum");
define("MODULE_FUNCTION", "function");
define("MODULE_IVAR", "ivar");
define("MODULE_MACRO", "macro");
define("MODULE_METHOD", "method");
define("MODULE_PROPERTY", "property");
define("MODULE_PROTOCOL", "protocol");
define("MODULE_STRUCT", "struct");
define("MODULE_TYPEDEF", "typedef");
define("MODULE_VARIABLE", "variable");

/**
 * Pascal syntax
 */

define("PROTOCOL_SUFFIX", "Protocol");								// protocols are suffixed with this word for type safety
define("CALLBACK_SUFFIX", "Callback");								// typedefs which are callbacks are suffixed with this word
define("POINTER_SUFFIX", "Ptr");											// types which are pointers in c (like NSRange *type) are suffixed with this word
define("ROOT_CLASS", "NSObject");											// if no super class is specified this class is assumed instead
define("RECORD_KEYWORD", "record");
define("PROCEDURE_RETURN_TYPE", "void");
define("BIT_PACKED_RECORD_KEYWORD", "bitpacked record");
define("DECLARED_CLASS_KEYWORD", "objcclass external");
define("DECLARED_CATEGORY_KEYWORD", "objccategory external");
define("DECLARED_PROTOCOL_KEYWORD", "objcprotocol external");
define("SOURCE_INDENT", "  ");
define("TYPDEF_ENUM_TYPE", "clong");									// named typedef enums will use this type when declared in pascal
define("OPAQUE_BLOCK_TYPE", "OpaqueCBlock");					// blocks will declared using this opaque type until they are supported in FPC
define("INLINE_ARRAY_SUFFIX", "Array");								// arrays which are defined inline parameters (like foo[]) will prefix their type with this word						
define("DEFAULT_PARAMATER_TYPE", "id");								// if not type is defined (in objective-c methods) this type is substituted
define("METHOD_SELECETOR_SEPARATOR", "_");						// the string to use when building method names from objective-c selector (like : in objective-c)
define("OBJC_SELECETOR_SEPARATOR", ":");							// objective-c selector separator character
define("UNDEFINED_PARAMETER_NAME_PREFIX", "param");		// unnamed parameters will be prefixed and indexed using this word
define("EXTERNAL_FUNCTION_CALLING_MODIFIER", "cdecl");
define("EXTERNAL_VARIABLE_SUFFIX", "cvar; external;");
define("KEYWORD_PROTECTION_SUFFIX", "_");							// all keywords are protected by suffixing this word
define("DUPLICATE_CLASS_METHOD_PREFIX", "class");			// class methods that are duplicates of instance methods are prefixed with this word
define("OPAQUE_TYPEDEF_TYPE", "OpaqueType");					// typedefs which reference structs that can't be found are changed to this type
define("FOUR_CHAR_CODE_REPLACE_PATTERN", "FourCharCode('$1')");					// constant strings of 4 characters are replaced with this pattern ($1 being the string contents)
define("IVAR_CONFLICT_PREFIX", "_");									// prefix all ivars with this to prevent unnecessary keyword protection in classes

/**
 * Other
 */

define("INVALID_OFFSET", -1);
define("PARSER_MEMORY_LIMIT", "1000M");		// -1 is no limit

/**
 * Messages
 */

define("MESSAGE_FRAMEWORKS_LOADED", false);			// Notify when frameworks are loaded
define("MESSAGE_PEAK_MEMORY_LIMITS", true);			// Notify at various times during parsing the peak memory usage (for debugging)
define("MESSAGE_INLINE_FUNCTIONS", false);			// Notify when inline functions are detected but ignored
define("MESSAGE_MEMORY_DEBUGGING", false);			// Notify when memory managed classes are destroyed (__destroy is called)
define("MESSAGE_ADD_SYMBOL", false);						// Notify symbols are added to the symbol table
define("MESSAGE_OPAQUE_TYPEDEFS", true);				// Notify when opaque typedefs are found
define("MESSAGE_DEFINE_EXTERNAL_MACRO", true);	// Notify external macros are dynamically adding to the framework
define("MESSAGE_DUPLICATE_IDENTIFIER", true);		// Notify when symbols names have already been declared (i.e. duplicate identifier)

?>
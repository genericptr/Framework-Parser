# Framework-Parser
Objective-C framework parser for Objective Pascal (FPC)

# Sections

* Basics
	* Specifying frameworks
	* Specifying framework definitions
	* Default frameworks
	* Auto-loading frameworks
	* Build commands
	* SDK search paths
	* Additional search paths
	* Custom unit templates
	* Building skeletons
	* Groups
	* Ignoring headers and frameworks
	* Additional framework definitions
	* Applying patches
	* Using skeletons
	* Command files
	* Stand-alone units
	* Parsing plain C headers from a directory
* Examples
* Shortcuts
* Defining Frameworks
	* Properties
	* Macros

=======================================

## 1) Basics

### a) Specifying frameworks:

The option -frameworks allows you to specify a single or list of frameworks to parse in a comma-separated list. For the parser to effectively locate the frameworks you must either specify -sdk or -search_paths as one of the options (see below). 

Frameworks should have a valid entry in frameworks.xml but if they do not you can specify a default framework (see below) which will basically auto-define the framework based of the framework specified. For example assume you want to parse the Automator framework but there is no reason to specify a unique entry in frameworks.xml because all settings are contained in "cocoa_base". To do this use the following option:

	-frameworks="cocoa_base:Automator"

Additionally you can prefix frameworks names by symbols which control their function. For example:

	^ will parse the framework but not print.
	# will parse the framework but not finalize symbols when complete (for testing mainly).

For example:

	-frameworks="^CoreFoundation, ^#Foundation, AppKit"

### b) Specifying framework definitions:

All frameworks specified must be accompanied by an XML framework definition file. By default the files base.xml and universal.xml (located in /defs) are loaded but you can add addition definitions by using -frameworks_xml or the shortcuts -macos and -ios which add all the frameworks definitions available for that OS.

### c) Default frameworks:

In parser switches -batch, -frameworks and -header you can specify a default framework by prefixing with the framework name and : such as  -batch="cocoa_base:/path/to/frameworks" which will parse all frameworks based off that framework. This is used for frameworks that don't have an entry in frameworks.xml, however if there is a matching framework entry it will override the default framework.

You can also use -default_framework to specify a default framework.

### d) Auto-loading frameworks:

If you specify -autoload_imported_frameworks all frameworks that are imported in the frameworks specified by -frameworks will be loaded and parsed.

For example, AppKit imports Foundation, ApplicationServices, CoreFoundation and others which will all be parsed if you only specify -frameworks=AppKit

### e) Build commands:

Specifying -build_commands=compiler/version will create .command scripts which you can use to test the parsed frameworks in the terminal (just double-click to open in Terminal.app). If needed the contents of the scripts can be controlled by editing the strings named $template_build_command in templates.php. 

Depending on the frameworks parsed you can specify which target the scripts are for by using one of the 3 options. You must also specify the FPC version as shown below.

	-build_commands=ppc/2.6.0
	-build_commands=i386/2.6.0
	-build_commands=arm/2.6.0

### f) SDK search paths:

When you specify -sdk the parser will search an array of paths for the SDK. This array is found in settings.php and must contain the macro %%SDK%% where the SDK name is in the path. For example:

$sdk_framework_directories = array("/Developer/SDKs/%%SDK%%/System/Library/Frameworks", "/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/%%SDK%%/System/Library/Frameworks");

As of Xcode 4.3 SDK's are now stored within the Xcode application bundle so a new switch -xcode is available. The new switch will expand the macro found in the 2 default search paths below. The default Xcode path is /Applications/Xcode.app since the /Developer directory is no longer in use like before.

	"%%XCODE%%/Contents/Developer/Platforms/MacOSX.platform/Developer/SDKs/%%SDK%%/System/Library/Frameworks"
	"%%XCODE%%/Contents/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/%%SDK%%/System/Library/Frameworks"


### g) Additional search paths:

You can specify additional search paths for frameworks if for example you have many frameworks specified in -frameworks but they exist in multiple locations.

To add search paths use -search_paths="/path".

### h) Custom unit templates:

By default the templates used when generating units .pas files is found in templates.php but you can specify custom template files by using -template. These files still follow the same temp ate format and macros such as [AVAILABILITY_MACROS] can be used.

For example the follow option will use the template for iPhoneAll.pas.

	-template="@/templates/iphoneall-template.txt"

### i) Building skeletons:

When -build_skeletons is enabled the parser will automatically generate the necessary files to compile the framework and place them within the directory specified by -out. Among the files needed are a root include file which includes all header files (which are printed as .inc files) and a .pas unit which includes the master include in the proper form. Additionally each framework produces a supporting InlineFunctions.inc and UndefinedTypes.inc which are used for storing hand-parsed code for the framework.

The parser will analyze the build order of the headers and print them properly in the root include so it's not recommended you do this by hand. If you wish to change change the main unit (like the uses clause which is common) it's preferred to modify the templates in templates.php or using various options in frameworks.xml.

### j) Groups:

Batch parses can be grouped together into a single unit using -group. Examples of this are iPhoneAll.pas and CocoaAll.pas which is grouped for ease of use since projects using these platforms typically always require at least a few frameworks frameworks and adding each file by hand to the uses clause can be tedious (FPC can not recurse the uses clause like C can).

The value for -group specifies the name of the unit, for example -group=iPhoneAll would generate iPhoneAll.pas.

You can still build all corresponding framework units in addition to the group unit by using -all_units which can optionally specify a template as the parameter (otherwise the default template is used and the value of -template is ignored).

Additionally you can control the list of frameworks added to the group unit by specifying the -group_frameworks switch with a comma-separated list of frameworks names. For example:

	-group_frameworks="Foundation, AppKit, CoreData"

### k) Ignoring headers and frameworks:

Certain frameworks or batch parses will contains headers or entire frameworks that you want to the parser to ignore by not parsing or printing. To ignore a header or framework use the -ignore option and specify a comma-separated list of header names or frameworks names.

For example:

	-ignore="vecLib.framework, vImage.framework, CoreDefines.h, AppKit.h"

### l) Additional framework definitions:

When parsing 3rd party frameworks you will typically want to specify an additional framework definition file, which you can do using -frameworks_xml then supplying a path to the XML file (or multiple files by separating paths by commas). Framework definitions are inheritable so you can specify existing framework definitions using <parent> and they will inherit all properties from that entry just as if they were in the same file.

For example the definition below inherits from cocoa_base.

	<?xml version="1.0" encoding="UTF-8" ?>
	<frameworks>			
		<framework>
			<parent>cocoa_base</parent>
			<name>DropboxSDK</name>
			<support_frameworks>Foundation</support_frameworks>
			<imports>MPDebug.h, MPOAuth.h, MPOAuthAPI.h, MPOAuthAPIRequestLoader.h, MPOAuthAuthenticationMethod.h</imports>
		</framework>
	</frameworks>

### m) Applying patches:

Because the parser is not always able to translate a framework 100% accurately it can apply .patch files (created with diff) automatically using the -patch option.

For example to apply the pre-made patch for iPhoneAll on iOS 5.0 SDK:

	-patch="@/patches/iphoneall-5.0.patch"

### n) Using skeletons:

Some frameworks may require hand added types and/or inline functions which any users will need to add manually to the parsers output directory, otherwise known as the "skeleton". If you specify the -skeleton option and provide a path to a skeleton directory it will be copied to the output directory (if it doesn't exist) and be used as the base for the parser.

For example to use the iPhoneAll skeleton which is pre-packaged with the parser:

	-skeletons="@/skeletons/iPhoneAll"

### o) Command files:

To facilitate sharing parser options you can make files which contain predefined options and specify the command by using -command. Command files are identical to sending direct command line options in the terminal except you add each option on a new line in the file.

For example the following command and file which parses iPhoneAll.

	php parser.php -out="~/dump" -command="@/commands/iphoneall5.0.txt"

	-batch="ios:/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/iPhoneOS5.0.sdk/System/Library/Frameworks"
	-ignore="vecLib.framework,vImage.framework"
	-group=iPhoneAll
	-patch="@/patches/iphoneall-5.0.patch"
	-template="@/templates/iphoneall-template.txt"
	-skeleton="@/skeletons/iPhoneAll"
	-class_definitions
	-build_skeletons

### p) Stand-alone units:

You can parse a single header as a stand-alone unit by using -header and -unit in conjunction (omitting -unit will produce an .inc include file). This is useful for 3rd party Objective-C headers which you can compile as static libraries in Xcode then link to the corresponding Objective Pascal unit. You must specify a base framework in the -header switch but currently you can not parse supporting frameworks using -frameworks so implicit pointer types like Cocoa classes will be parsed as pointers (i.e. NSObject will be NSObjectPtr). You must add additional units, cleanup class types and optionally link the library using {$linklibrary} by hand in the parsed unit file.

For example an Objective-C header from iOS 6.0 can be parsed using these options:

	php parser.php -header="ios:~/Desktop/Reachability.h" -xcode="/Applications/Xcode45-DP3.app" -ios -sdk="iPhoneOS6.0.sdk" -unit -show

## 2) Examples:

### Batch parse iOS SDK:

	php parser.php -out="~/dump" -batch="ios:/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/iPhoneOS5.0.sdk/System/Library/Frameworks" -ios -class_definitions -build_skeletons

Batch parsing will search the entire directory specified in -batch and parse all frameworks including private frameworks which may be nested within other frameworks. You should specify a default framework like "ios:" in the example above so that frameworks that don't have entries in framework.xml will have a valid framework to parse from.

Note: the -xcode switch and %%XCODE%% macro can be used in the -batch path (see the section SDK search paths above).

### Batch parse system frameworks:

	php parser.php -out="~/dump" -batch="cocoa_base:/System/Library/Frameworks" -macos -class_definitions -build_skeletons

Be warned that parsing /System/Library/Frameworks on my MacBook 2.4GHz Intel Core 2 Duo took 40 minutes and 900MB of RAM at peak usage.

### Parse framework from SDK:

php parser.php -out="~/dump" -frameworks="AppKit" -sdk="MacOSX10.7.sdk" -all -macos -autoload_imported_frameworks -class_definitions -build_skeletons

Using the -sdk option you can specify an SDK name such as MacOSX10.7.sdk or iPhoneOS5.0.sdk and the parser will search for the using on the system from an array of supplied search paths.

### Group frameworks:

There is an example of how to parse iPhoneAll using -group and a custom template using -template:

	php parser.php -out="~/Desktop/iPhoneAll" "-batch="ios:/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/iPhoneOS5.0.sdk/System/Library/Frameworks" -ignore="vecLib.framework,vImage.framework" -group=iPhoneAll -template="@/templates/iphoneall-template.txt" -ios -class_definitions -build_skeletons

### Parse 3rd party framework from specific directory:

In the example below the framework DropboxSDK is parsed from ~/Desktop/dropbox-ios-sdk-1.1 which is specified using -search_paths. Foundation.framework is also parsed for supporting types (but not printed as the ^ prefix is used) so the MacOSX10.7 SDK is specified using -sdk. Typically you may need to specify an additional framework definition using -frameworks_xml as seen below.

	php parser.php -out="~/dump" -frameworks="^Foundation, DropboxSDK" search_paths="~/Desktop/dropbox-ios-sdk-1.1" -frameworks_xml="~/Desktop/dropbox-ios-sdk-1.1/DropboxSDK.xml" -sdk="MacOSX10.7.sdk" -all -class_definitions -build_skeletons

### Parse Objective-C headers to Objective Pascal units:

Often source code is distributed as a directory containing .m and .h files without a framework structure. The example below uses the -dir option to parse headers from a directory (with Foundation and 10.8 SDK for symbols) and outputs as .pas units. Note the prefix : is used to specify the base framework of the directory.

	php parser.php -out="~/dump" -frameworks="^Foundation" -dir="cocoa_base:~/UKKQueue" -sdk=MacOSX10.8.sdk -macos -unit

### Parse single header from SDK and print output:

### Parse a header from AppKit.framework via the 10.7 SDK:

	php parser.php -header="AppKit:NSWindow.h" -sdk="MacOSX10.7.sdk" -macos -show

### Parse any generic Objective-C header:

	php parser.php -header="cocoa_base:~/Desktop/SomeCocoaFile.h" -show

This is typically for testing the parser since parsing single headers will likely produce errors since supporting frameworks are missing. Headers that don't contain Objective-C however have a better chance of working since the types therein may not rely on other frameworks, which is seldom the case with Objective-C headers.

### Parsing plain C headers from a directory:

You can parse plain C headers from any directory using -dir and -plain_c. This is useful for any project in general or frameworks that are not formatted like Apple's system frameworks which include "umbrella files" which the parser uses to find headers.

Using the plain C option will ignore external macros that the Apple frameworks require and is more compatible with generic C headers. You can still use -units to print individual unit files or -build_skeletons to merge all units into a single .pas unit which includes all the headers in the directory as .inc files.

Optionally you can specify a framework definition to add settings to the parser even though what you're parsing may be technically be a framework.

Fore example the command below will define a framework (test) and parse all files from the given directory in plain C mode.

	php parser.php -out="." -frameworks_xml="test.xml" -dir="test:/path/to/headers" -plain_c

## 3) Shortcuts

The parser provides some pre-made commands for common SDK versions that most Objective Pascal users need. These shortcuts specify all the required options in addition to applying some patches and building from a pre-made skeleton. 

The goal is to provide a single command that will guarantee fully compilable headers without any further modification. This is particularly important with iOS where Apple prohibits us to distribute even "derived" copies of the SDK.

To see which commands are available browser the /parser/commands directory.

Using iPhoneAll shortcut for iOS SDK 5.0 :

	php parser.php -out="~/iPhoneAll" -command="@/commands/iphoneall5.0.txt"

## 4) Defining Frameworks

When parsing frameworks the most important step is defining the frameworks various settings via an XML file known as framework.xml (although you can specify more than one file this is the general name used).

Framework definition properties are inheritable which means one framework can inherit the properties from an existing framework. Below is the list of available properties.

### Properties: 

`<name>` - The name of the framework without extension. For static frameworks (see below) the name does not represent an actual .framework directory but is used just for reference (see cocoa_base for example).

`<static>` - 1 = true, 0 = false. Static frameworks are used for referencing settings but do not represent an actual framework so they can not be parsed. The framework "base" is an example of a static framework.

`<root>` - Partial path of the master include file in the parser output directory. This is typically not changed and is defined as <root>/%%LC_NAME%%/Sources.inc</root> in "base".

`<umbrella>` - The name of the "umbrella" headers in the frameworks header directory which imports all relevant headers for the framework. This is not a requirement for frameworks however and is more of a convention used by Apple which states the header named the same as the framework is the umbrella (defined as %%NAME%%.h in "base"). For example in AppKit.framework AppKit.h is the umbrella which imports and headers required for AppKit.

`<ignore_types>` - Comma-separated list of types (may be regular expressions if no commas are in the pattern) which the parser will ignore. These types apply to: typesdefs, structs, classes and defines.

`<ignore_headers>` - Comma-separated list of header names that will be ignored in the framework.

`<ignore_lines>` - Array of lines that will be skipped by the parser.

	`<ignore_lines>
		<line>#define FOO</line>
		<line>@class FOO</line>
	</ignore_lines>`

`<uses>` - Comma-separated list of units to include in the uses clause when generating units. This list is inserted into templates using the [USES] macro (see templates.php).

`<imports>` - Comma-separated list of headers to "import" into the framework (like #import in C). Typically you can use <umbrella> to load all required headers but in some frameworks you may need to specify additional headers or the umbrella header does not exist.

`<implicit_pointers>` - Comma-separated list of types that are "implicit pointers", I.E. these types are not parsed as pointers even with * in the their usage (typically used for Objective-C classes).

`<external_macros>` - The parser will make an attempt to parser #defines that specify the "extern" keyword in C but if it fails you must specify external macros explicitly or external types like variables and functions will be ignored.

`<remove_macros>` - **DEPRECATED** use <replacement_patterns> instead. Array of regular expression to be removed entirely. 

	`<remove_macros>
		<value>/ATTRIBUTE_PACKED/</value>
	</remove_macros>`

`<availability_macros>` - Array of regular expression patterns/replacements that will be included into the Pascal source next to the symbol it was declared. These are macros are used to notify users a symbol is no longer available, available as of certain SDK or deprecated.

	`<availability_macros>
		<macro>
			<pattern>/AVAILABLE_\w+_VERSION_(\d+)_(\d+)_AND_LATER\b/i</pattern>
			<replacement>{ available in $1.$2 and later }</replacement>
		</macro>
	<availability_macros>`

`<inline_macros>` - Comma-separated list of macros that specify the C keyword "inline" which the parser requires in order to skip inline functions that it can not translate properly.

`<replacement_patterns>` - Array of regular expression patterns/replacements that will be stripped from the C source entirely.

	`<replacement_patterns>
		<pattern>
			<pattern>/CALLBACK_API(_C|_STDCALL|_C_STDCALL)*\s*\(\s*(\w+)\s*,\s*(\w+)\s*\)/i</pattern>
			<replacement>$2 (* $3)</replacement>
		</pattern>
	</replacement_patterns>`

`<replace_types>` - Array of C types and their Pascal equivalent they will be replaced with (in key=value pair format). If you specify a key wrapped in / it will be treated as regular expression. By default all types are treated as case-insensitive and spaces are expanded to /s+ so if you want other behavior you define the regular expression manually by wrapping the key in /.

	`<replace_types>
		<value>void=pointer</value>
		<value>/^bool$/=cbool</value>
		<value>/^BOOL$/=boolean</value>
	</replace_types>`

`<pointer_types>` - Array of C types and their preferred pointer type to be used if the type is encoded as a pointer.

`<support_frameworks>` - Comma-separated list of framework names to import. Even if a framework has been parsed in the current batch frameworks can not see each others types unless they import each other.

The parser will attempt to import frameworks automatically for support types when it encounters lines like #import <Foundation/Foundation.h> but currently it does not recurse into headers so lines such as #import <Cocoa/Cocoa.h> will not import all frameworks in Cocoa.h.

### Macros:

You can specify various macros in the XML which will be replaced by appropriate values depending on the framework being parsed.

`%%NAME%%`

The name of the framework as <name>

`%%LC_NAME%%`

Same as %%NAME%% but the name is in all lower case characters.

`%%UC_NAME%%`

Same as %%NAME%% but the name is in all upper case characters.

`%%ABBRV_NAME%%`

The abbreviated name of the framework as is often used in C macros. The abbreviation is made by using only the upper case characters in the framework name (as defined in <name> or in the file name for batch parses) so CoreVideo which be CV.

`%%PREGEX_NAMES%%`

Perl-compatible regular expression pattern for the normal and abbreviated name of the framework. For example AppKit would be (UI|UIKit)+. This macro should only be used in properties that use regular expressions.

`%%SDK%%`

Value of the -sdk switch. This macro is used for path names usually.


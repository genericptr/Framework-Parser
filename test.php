<?php

require_once("source/utilities.php");

$todo = <<<HEADER
TO-DO:
	
	• add error reporting command line options
			-errors=fatal+message+note+warning
	• block/function pointers in returns types (see methods.h)	
	√ protocols need to print super class actually which is allowed
	• empty macros from class scope types being removed (see AVAsset.inc)		
	• format_array_type needs to add dynamic array types to the symbol table like callbacks
	  we also need a system to handle dynamic symbol adding now that printing is handled via scopes
	• we always need to build skeletons to get imported frameworks but we don't always want to
		print them.
			- rename to analyze_framework and always call
	• make availability macro/replacement pattern callbacks to post process the results:
		for example __OSX_AVAILABLE_STARTING (__MAC_10_4, __IPHONE_NA) macros return a value of "__MAC_10_4, __IPHONE_NA" so we could
		make a callback to process and return "Mac OS 10.4, iOS not available"
		
		use call_user_func("post_process_ios_availability_macro", $1);
		
		<macro>
			<pattern>/\s*[_]*\w+_AVAILABLE\s*\((.*)\)\s*/i</pattern>
			<replacement>{ available in $1 }</replacement>
			<function>post_process_ios_availability_macro</function>
		</macro>
	• Accelerate and vecLib have cirular unit references which we need to protect against, if not really complicated
	• we need a way to define macros in frameworks.xml or templates in general
	
	• parse plain folders for headers 
		iOS requires all these headers:
		/iPhoneSimulator4.0.sdk/usr/include
		
		we may need another command line option because 
		the framework.xml doesn't accomidate general headers in the sdk
		-sdk_headers="/usr/include/dh.h"
		
	• iPhoneAll
		√ don't add DefinedClassesXXX units into uses unless the file actually exists!
		√ don't add frameworks to group template unless they were actually parsed! 
		- we need ignore frameworks for -group otherwise vecLib gets printed which we don't want 
			can we make -batch ignore frameworks instead?
	
	• in -group mode we need to have a differnt policy with duplicate variables, i.e. ignore them completely.	
		1) NSString declares UniChar again.
		   do we need a <ignore_header_types>
											<header>
												<name>NSString.h</name>
												<types>UniChar</types>
											</header>
										</ignore_header_types>

			B) don't declare in NSManagedObjectContext
			NSErrorMergePolicy: id; cvar; external;
			NSMergeByPropertyStoreTrumpMergePolicy: id; cvar; external;
			NSMergeByPropertyObjectTrumpMergePolicy: id; cvar; external;
			NSOverwriteMergePolicy: id; cvar; external;
			NSRollbackMergePolicy: id; cvar; external;
		
	• when in -group mode make another option to ALSO print all individual framwork units
	  because we may need these also as an option.
	
		-framework_units? -group_include_units?
		
HEADER;
//print($todo);
 
$header = <<<HEADER
typedef enum _CGInterpolationQuality CGInterpolationQuality;

enum CGInterpolationQuality {
    kCGInterpolationDefault = 0,
    kCGInterpolationNone = 1,
    kCGInterpolationLow = 2,
    kCGInterpolationMedium = 4,
    kCGInterpolationHigh = 3
};

HEADER;
//show_string_offset(array(104), file_get_contents(getcwd()."/tests/universal-headers.h"));

//if (preg_match("/(typedef)*\s*enum\s*(\w+)*\s*\{/i", $header, $captures, PREG_OFFSET_CAPTURE, 1)) {
//	print_r($captures);
//}
//die;

//if (preg_match("/(typedef)*\s*(const)*\s*(struct|union)+\s*(\w*)\s*\{/i", $header, $captures)) print_r($captures);
//preg_match_all("/(\w+)\s*:\s*(\(.*?\)|)\s*(\w+)/", $header, $captures);
//print_r($captures);
//die;

function parse_header () {
	$cwd = getcwd();
	
	//php /Developer/ObjectivePascal/parser/test.php -header="Foundation:~/Desktop/header.h" -macos -show
	
	//$GLOBALS["argv"][] = "-header=\"ios:CGGeometry.h\"";
	//$GLOBALS["argv"][] = "-header=\"ios:/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/iPhoneOS5.0.sdk/System/Library/Frameworks/CoreGraphics.framework/Headers/CGBase.h\"";
	//$GLOBALS["argv"][] = "-header=\"carbon_base:/System/Library/Frameworks/CoreServices.framework/Versions/A/Frameworks/CarbonCore.framework/Versions/A/Headers/Files.h\"";
	//$GLOBALS["argv"][] = "-header=\"carbon_base:/System/Library/Frameworks/Carbon.framework/Versions/A/Frameworks/HIToolbox.framework/Versions/A/Headers/MacWindows.h\"";
	//$GLOBALS["argv"][] = "-header=\"Foundation:/Developer/SDKs/MacOSX10.7.sdk/System/Library/Frameworks/Foundation.framework/Versions/C/Headers/NSObjCRuntime.h\"";
	//$GLOBALS["argv"][] = "-header=\"ios:$cwd/tests/universal-headers.h\"";
	//$GLOBALS["argv"][] = "-header=\"CoreFoundation:$cwd/tests/changes_6_0.h\"";
	//$GLOBALS["argv"][] = "-header=\"CoreImage:$cwd/tests/changes_10_8.h\"";
	$GLOBALS["argv"][] = "-header=\"cocoa_base:$cwd/tests/extern_c.h\"";
	
	//$GLOBALS["argv"][] = "-ios";
	$GLOBALS["argv"][] = "-macos";
	$GLOBALS["argv"][] = "-show";
	$GLOBALS["argv"][] = "-externc";
}

function parse_unit () {
	$GLOBALS["argv"][] = "-header=\"cocoa_base:~/Downloads/MAAttachedWindow/MAAttachedWindow.h\"";
	$GLOBALS["argv"][] = "-unit";
	$GLOBALS["argv"][] = "-out=\"~/Desktop\"";
}

function parse_frameworks_ios () {
	$GLOBALS["argv"][] = "-frameworks=\"OpenGLES\"";
	$GLOBALS["argv"][] = "-out=\"~/Desktop/iosall\"";
	$GLOBALS["argv"][] = "-sdk=iPhoneOS5.0.sdk";
	
	// ??? kill for -search_paths
	//$GLOBALS["argv"][] = "-framework_directory=\"/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/iPhoneOS5.0.sdk/System/Library/Frameworks\"";
	
	// we need this for iOSAll.pas!
	// FRAMEWORK_LOADED defines need to moved also to prevent conflicts in undefined types
	//$GLOBALS["argv"][] = "-group="iOSAll:Foundation,CoreImage,OpenGLES,QuartzCore,UIKit"";
	
	//$GLOBALS["argv"][] = "-show";
	//$GLOBALS["argv"][] = "-dryrun";
	//$GLOBALS["argv"][] = "-diagnostics";
	$GLOBALS["argv"][] = "-all";
	//$GLOBALS["argv"][] = "-opaque_types";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
	//$GLOBALS["argv"][] = "-autoload_imported_frameworks";
	$GLOBALS["argv"][] = "-build_commands=arm/2.6.0";
}

function parse_frameworks () {
	$GLOBALS["argv"][] = "-frameworks=\"CoreFoundation\"";
	//$GLOBALS["argv"][] = "-default_framework=\"cocoa_base\"";
	$GLOBALS["argv"][] = "-out=\"~/Desktop/Parser\"";
	$GLOBALS["argv"][] = "-sdk=iPhoneOS6.0.sdk";
	$GLOBALS["argv"][] = "-xcode=/Applications/Xcode45-DP3.app";
	$GLOBALS["argv"][] = "-all";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
	//$GLOBALS["argv"][] = "-autoload_imported_frameworks";
	$GLOBALS["argv"][] = "-build_commands=i386/2.6.0";
}

function parse_cocoa_all () {	
	/*
	PrintCore - PDEPluginInterface
	AudioUnit - AUCocoaUIView (the rest are in MacOSAll)
	
	one class per framework
	AppleScriptKit
	AppleScriptObjC
	*/
	
	/*
	Support frameworks:
	AudioUnit
	*/
	
	// Carbon important
	/*
		
		ImageIO
		CoreGraphics
		CoreFoundation
		OpenGL
		LaunchServices

		
	*/
	$GLOBALS["argv"][] = "-out=\"~/Desktop/CocoaAll\"";
	$GLOBALS["argv"][] = "-command=\"@/commands/cocoaall10.7.txt\"";
	$GLOBALS["argv"][] = "-build_commands=i386/2.7.1";
}

function parse_ios_all () {
	$GLOBALS["argv"][] = "-out=\"~/Desktop/iPhoneAll\"";
	$GLOBALS["argv"][] = "-command=\"@/commands/iphoneall5.0.txt\"";
	$GLOBALS["argv"][] = "-build_commands=arm/2.7.1";
}


function parse_batch_system () {
	$GLOBALS["argv"][] = "-out=\"~/Desktop/MacOS10.7\"";
	$GLOBALS["argv"][] = "-batch=\"cocoa_base:/System/Library/Frameworks\"";
	$GLOBALS["argv"][] = "-ignore=\"glext.h,gl.h\"";
	
	// quicktime.framework
	// krb5.h
	
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
	$GLOBALS["argv"][] = "-build_commands=i386/2.6.0";
}

function parse_batch_ios () {
	$GLOBALS["argv"][] = "-out=\"~/Desktop/ios5.0\"";
	//$GLOBALS["argv"][] = "-out=\"@/skeletons/ios5.0\"";
	$GLOBALS["argv"][] = "-batch=\"ios:/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/iPhoneOS5.0.sdk/System/Library/Frameworks\"";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
	$GLOBALS["argv"][] = "-build_commands=arm/2.6.0";
}

function parse_external_framework () {

	/*
	note that we have 3 umbrellas in DropboxSDK (JSON, MPOAuth, DropboxSDK).
	if we specify MPOAuth in <imports> the framework sorter (should change name to FrameworkAnanlyzer) 
	it should be able to add headers to path
	
	for files outside of the umbrellas should we parse them as standalone files? we have some support
	units in there also which could be separate like UIAlertView+Dropbox.h
	*/
	
	// ??? make -preserve="unit" so the master unit isn't changed. we can expand params later...
	
	// ??? we can add -all_headers to ignore the umbrella and imports etc... this could be in
	// frameworks.xml also....
	
	$GLOBALS["argv"][] = "-frameworks=\"^Foundation, DropboxSDK\"";
	$GLOBALS["argv"][] = "-search_paths=\"~/Desktop/dropbox-ios-sdk-1.1\"";
	$GLOBALS["argv"][] = "-frameworks_xml=\"~/Desktop/dropbox-ios-sdk-1.1/DropboxSDK.xml\"";
	$GLOBALS["argv"][] = "-out=\"~/Desktop/DropboxSDK\"";
	$GLOBALS["argv"][] = "-sdk=MacOSX10.7.sdk";
	$GLOBALS["argv"][] = "-all";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
}

function parse_directory ($directory) {
	//$GLOBALS["argv"][] = "-frameworks=\"sharekit\"";
	$GLOBALS["argv"][] = "-umbrella=\"$directory/SHK.h\"";
	//$GLOBALS["argv"][] = "-ignore=\"uthash.h,utlist.h\"";
	$GLOBALS["argv"][] = "-out=\"~/Desktop/Parser/ShareKit\"";
	$GLOBALS["argv"][] = "-sdk=MacOSX10.7.sdk";
	// ??? we need something like this so master unit can be generated by hand
	$GLOBALS["argv"][] = "-custom_unit";
	$GLOBALS["argv"][] = "-all";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
	$GLOBALS["argv"][] = "-build_commands=arm/2.6.0";
}

function parse_directory_2 ($directory) {
	//php parser.php -dir="ios:~/Downloads/ShareKit/Classes/ShareKit/Core" -out="~/Desktop/Parser" -ios
	$GLOBALS["argv"][] = "-out=\"~/Desktop/Parser\"";
	$GLOBALS["argv"][] = "-dir=\"ios:$directory\"";
	$GLOBALS["argv"][] = "-ios";
	$GLOBALS["argv"][] = "-build_skeletons";
}

function clean_directory () {
	$GLOBALS["argv"][] = "-clean=\"~/Desktop/CocoaAll_skel\"";
}

// patch -p0 < iPhoneAll.patch

/*
	diff directories:
	
	1) Parse the SDK
	2) Copy the output diretory and suffix with _old.
	3) Make changes to the original output directory by hand
	4) Run the command below with proper naming and suffix the .patch with the SDK version for clarity
	If -out was ~/Desktop/CocoaAll:
	
		cd ~/Desktop
		diff -rupN CocoaAll_old/ CocoaAll/ > cocoaall-10.8.patch
		diff -rupN iPhoneAll_old/ iPhoneAll/ > iphoneall-6.0.patch
		
*/

function find_frameworks_with_objective_c ($directory, $framework = null) {
	//print("$directory\n");
	$frameworks = array();
	$contents = directory_contents($directory, true);
	foreach ($contents as $path) {
		$name = basename($path);
		
		if (is_dir($path)) {
			
			// recurse frameworks
			if (file_extension($name) == "framework") {
				$frameworks = array_merge($frameworks, find_frameworks_with_objective_c($path, $name));
			} else {
				$frameworks = array_merge($frameworks, find_frameworks_with_objective_c($path, $framework));
			}
			
		} else {
			
			// find objective-c in headers
			if ((file_extension($name) == "h") && ($framework)) {
				$lines = file($path);
				foreach ($lines as $line) {
					
					if (preg_match("/@(protocol|interface)+/", $line)) {
						print("+ $framework ($name): $line");
						$frameworks[] = $framework;
						return $frameworks;
					}
					
				}
			}
			
		}
	}
	
	return array_unique($frameworks);
}

//$frameworks = find_frameworks_with_objective_c("/Developer/SDKs/MacOSX10.7.sdk");
//print_r($frameworks);
//die;

parse_header();
//parse_frameworks();
//parse_frameworks_ios();
//parse_batch_system();
//parse_batch_ios();
//parse_external_framework();
//parse_unit();

//parse_cocoa_all();
//parse_ios_all();
//clean_directory();
//parse_directory("~/Desktop/Projects/Dev/OpenGL/Examples/cocos2d-iphone-1.0.0-rc2/cocos2d");
//parse_directory("~/Downloads/ShareKit/Classes/ShareKit/Core");
//parse_directory_2("~/Downloads/ShareKit/Classes/ShareKit/Core");

require("parser.php");
//print("=================\n");

///Developer/SDKs/MacOSX10.7.sdk/usr/include/TargetConditionals.h
///Developer/SDKs/MacOSX10.7.sdk/usr/include/AvailabilityMacros.h
///Users/ryanjoseph/Desktop/iosall/iPhoneOS5.0.sdk/usr/include/Availability.h

?>
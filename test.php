<?php

require_once("source/utilities.php");

function parse_header () {
	$cwd = getcwd();
	
	//$GLOBALS["argv"][] = "-header=\"ios:CGGeometry.h\"";
	$GLOBALS["argv"][] = "-header=\"AppKit:$cwd/tests/changes_10_15.h\"";
	
	//$GLOBALS["argv"][] = "-ios";
	$GLOBALS["argv"][] = "-macos";
	$GLOBALS["argv"][] = "-unit";
	$GLOBALS["argv"][] = "-show";
	//$GLOBALS["argv"][] = "-plain_c";
	//$GLOBALS["argv"][] = "-frameworks_xml=\"~/Desktop/gdl.xml\"";
	//$GLOBALS["argv"][] = "-v";
}

function parse_test ($name, $sdk) {
	$cwd = getcwd();
	$GLOBALS["argv"][] = "-header=\"AppKit:$cwd/tests/$name\"";
	$GLOBALS["argv"][] = "-$sdk";
	$GLOBALS["argv"][] = "-unit";
	$GLOBALS["argv"][] = "-show";
	//$GLOBALS["argv"][] = "-v";
}

function parse_macos_header () {
	$cwd = getcwd();
	$GLOBALS["argv"][] = "-header=\"Foundation:$cwd/tests/cblocks.h\"";
	$GLOBALS["argv"][] = "-macos";
	$GLOBALS["argv"][] = "-unit";
	$GLOBALS["argv"][] = "-show";
	// $GLOBALS["argv"][] = "-v";
}

function parse_appkit_framework () {
	$GLOBALS["argv"][] = "-frameworks=Foundation,AppKit";
	$GLOBALS["argv"][] = "-frameworks_xml=@/defs/macos.xml";
	$GLOBALS["argv"][] = "-out=~/Desktop/MacOS_10_15";
	$GLOBALS["argv"][] = "-sdk=MacOSX10.15.sdk";
	$GLOBALS["argv"][] = "-all";
	$GLOBALS["argv"][] = "-safe";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
	$GLOBALS["argv"][] = "-build_commands=x86/3.0.4";
}

function parse_macos_framework ($names) {

	$GLOBALS["argv"][] = "-frameworks=$names";
	$GLOBALS["argv"][] = "-out=~/Desktop/CocoaAll_10_15";
	$GLOBALS["argv"][] = "-group=CocoaAll";
	$GLOBALS["argv"][] = "-group_frameworks=\"Foundation, CoreImage, QuartzCore, CoreData, AppKit\"";
	$GLOBALS["argv"][] = "-all_units=\"@/templates/mac-unit-template.txt\"";
	$GLOBALS["argv"][] = "-template=\"@/templates/cocoaall-template.txt\"";
	$GLOBALS["argv"][] = "-skeleton=\"@/skeletons/CocoaAll_10_15\"";
	$GLOBALS["argv"][] = "-sdk=MacOSX10.15.sdk";
	$GLOBALS["argv"][] = "-macos";
	$GLOBALS["argv"][] = "-all";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-v";
	$GLOBALS["argv"][] = "-safe";
	$GLOBALS["argv"][] = "-build_skeletons";
	$GLOBALS["argv"][] = "-build_commands=x86/3.0.4";
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

function parse_cocoa_all () {	
	$GLOBALS["argv"][] = "-out=\"~/Desktop/CocoaAll_10_15\"";
	$GLOBALS["argv"][] = "-command=\"@/commands/cocoaall10.15.txt\"";
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
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
	$GLOBALS["argv"][] = "-build_commands=i386/2.6.0";
}

function parse_batch_ios () {
	$GLOBALS["argv"][] = "-out=\"~/Desktop/ios5.0\"";
	$GLOBALS["argv"][] = "-batch=\"ios:/Developer/Platforms/iPhoneOS.platform/Developer/SDKs/iPhoneOS5.0.sdk/System/Library/Frameworks\"";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
	$GLOBALS["argv"][] = "-build_commands=arm/2.6.0";
}

function parse_external_framework () {
	$GLOBALS["argv"][] = "-frameworks=\"^Foundation, DropboxSDK\"";
	$GLOBALS["argv"][] = "-search_paths=\"~/Desktop/dropbox-ios-sdk-1.1\"";
	$GLOBALS["argv"][] = "-frameworks_xml=\"~/Desktop/dropbox-ios-sdk-1.1/DropboxSDK.xml\"";
	$GLOBALS["argv"][] = "-out=\"~/Desktop/DropboxSDK\"";
	$GLOBALS["argv"][] = "-sdk=MacOSX10.7.sdk";
	$GLOBALS["argv"][] = "-all";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-build_skeletons";
}

function parse_header_external_directory () {
	// $header = "/Applications/Xcode.app/Contents/Developer/Platforms/MacOSX.platform/Developer/SDKs/MacOSX.sdk/System/Library/Frameworks/Metal.framework/Versions/A/Headers/MTLArgument.h";
	$header = "/Users/ryanjoseph/Desktop/metal/metal_test.h";

	$GLOBALS["argv"][] = "-header=\"Metal:$header";
	$GLOBALS["argv"][] = "-frameworks=\"^Foundation, Metal\"";
	$GLOBALS["argv"][] = "-frameworks_xml=\"~/Desktop/metal/metal.xml\"";
	$GLOBALS["argv"][] = "-sdk=MacOSX10.12.sdk";
	$GLOBALS["argv"][] = "-all";
	$GLOBALS["argv"][] = "-class_definitions";
	$GLOBALS["argv"][] = "-v+";
	$GLOBALS["argv"][] = "-show"; // don't output anything
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
	$GLOBALS["argv"][] = "-out=\"~/Desktop/Parser\"";
	$GLOBALS["argv"][] = "-dir=\"ios:$directory\"";
	$GLOBALS["argv"][] = "-ios";
	$GLOBALS["argv"][] = "-build_skeletons";
}

// parse_test('generics.h', 'macos');
// parse_cocoa_all();
parse_macos_header();
// parse_appkit_framework();
// parse_macos_framework('Foundation,AppKit');
// parse_macos_framework('^Foundation,Metal,MetalKit');
// parse_frameworks();
// parse_frameworks_ios();
// parse_batch_system();
// parse_batch_ios();
// parse_external_framework();
// parse_unit();
// parse_header_external_directory();

print(implode(" ", $GLOBALS["argv"])."\n");

require("parser.php");

?>
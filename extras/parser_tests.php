<?php

ini_set("error_reporting", E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once('source/language_utilities.php');

function test_objc_generics () {

	$result = clean_objc_generics('NSArray<NSURL *>, NSSet<NSURL *>');
	print("✅ $result\n");

	$result = clean_objc_generics('NSDictionary<NString *, NSArray<NSURL *>>');
	print("✅ $result\n");

	$result = clean_objc_generics('NSArray<NSURL *>');
	print("✅ $result\n");

	$result = clean_objc_generics('NSDictionary<NSURL *, NSURL *>');
	print("✅ $result\n");
}	

	
function test_cblocks () {
	$result = clean_objc_generics('(id (^)(NSError *err, NSErrorUserInfoKey userInfoKey))');
	print("✅ $result\n");
}


test_objc_generics();

?>
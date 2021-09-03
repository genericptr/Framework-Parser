<?php

ini_set("error_reporting", E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once('source/language_utilities.php');
require_once('source/errors.php');
require_once('source/scanner.php');

function verify(string $result, string $expected) {
	if ($result == $expected) {
		print("✅ $result\n");
	} else {
		print("🔴 got ".ansi_string(array(ANSI_BACK_RED), $result).", expected ".ansi_string(array(ANSI_BACK_GREEN), $expected)." \n");
	}
}

function test_objc_generics () {


	$result = clean_objc_generics('NSOrderedCollectionChange<id> *(^)(NSOrderedCollectionChange<ObjectType> *) block');
	verify($result, 'NSOrderedCollectionChange *(^)(NSOrderedCollectionChange *) block');

	$result = clean_objc_generics('NSArray<NSURL *>, NSSet<NSURL *>');
	verify($result, 'NSArray, NSSet');

	$result = clean_objc_generics('NSDictionary<NString *, NSArray<NSURL *>>');
	verify($result, 'NSDictionary');

	$result = clean_objc_generics('NSArray<NSURL *>');
	verify($result, 'NSArray');

	$result = clean_objc_generics('NSDictionary<NSURL *, NSURL *>');
	verify($result, 'NSDictionary');

	$result = clean_objc_generics('NSArray<NSDictionary<NSString *, id> *>');
	verify($result, 'NSArray');
}


test_objc_generics();

?>
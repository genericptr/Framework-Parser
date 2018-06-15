We use -search_paths because the version in Xcode is newer than the system verison but we could exclude -search_paths if we didn't care and the default system frameworks directory would be scanned.

Metal.framework

	php parser.php -out="~/files" -frameworks="^Foundation, Metal" -frameworks_xml="metal.xml" -search_paths="/Applications/Xcode.app/Contents/Developer/Platforms/MacOSX.platform/Developer/SDKs/MacOSX.sdk/System/Library/Frameworks" -sdk="MacOSX10.13.sdk" -all -class_definitions -build_skeletons

MetalKit.framework

	php parser.php -out="~/files" -frameworks="^Foundation, ^Metal, MetalKit" -frameworks_xml="metal.xml" -search_paths="/Applications/Xcode.app/Contents/Developer/Platforms/MacOSX.platform/Developer/SDKs/MacOSX.sdk/System/Library/Frameworks" -sdk="MacOSX10.13.sdk" -all -class_definitions -build_skeletons

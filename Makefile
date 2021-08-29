
COCOA_ALL_FRAMEWORKS = Foundation,AppKit
PHP = php7

all: test

test:
	term "${PHP} test.php"

build_10_15:
	${PHP} parser.php -frameworks="${COCOA_ALL_FRAMEWORKS}" -out=~/Desktop/CocoaAll_10_15 -group=CocoaAll -group_frameworks="Foundation, CoreImage, QuartzCore, CoreData, AppKit" -all_units="@/templates/mac-unit-template.txt" -template="@/templates/cocoaall-template.txt" -skeleton="@/skeletons/CocoaAll_10_15" -sdk=MacOSX10.15.sdk -macos -all -class_definitions -v -safe -build_skeletons

build_11_0:
	${PHP} parser.php -frameworks="${COCOA_ALL_FRAMEWORKS}" -out=~/Desktop/CocoaAll_11_0 -group=CocoaAll -group_frameworks="Foundation, CoreImage, QuartzCore, CoreData, AppKit" -all_units="@/templates/mac-unit-template.txt" -template="@/templates/cocoaall-template.txt" -skeleton="@/skeletons/CocoaAll_10_0" -sdk=MacOSX11.3.sdk -macos -all -class_definitions -v -safe -build_skeletons
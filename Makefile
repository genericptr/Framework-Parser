
COCOA_ALL_FRAMEWORKS = Foundation, AppKit
GROUP_FRAMEWORKS = Foundation, CoreImage, QuartzCore, CoreData, AppKit

PHP = php7
CWD = $(shell pwd)

TEST_CBLOCKS = parser.php -header="Foundation:${CWD}/tests/cblocks.h" -macos -unit -show
TEST_10_15 = parser.php -header="Foundation:${CWD}/tests/changes_10_15.h" -macos -unit -show

TEST_MACOS_11_0 = parser.php -frameworks=\"${COCOA_ALL_FRAMEWORKS}\" -out=~/Desktop/CocoaAll_11_0 -group=CocoaAll -group_frameworks=\"${GROUP_FRAMEWORKS}\" -all_units=\"@/templates/mac-unit-template.txt\" -template=\"@/templates/cocoaall-template.txt\" -skeleton=\"@/skeletons/CocoaAll_11_0\" -sdk=MacOSX11.3.sdk -macos -all -class_definitions -v -safe -build_skeletons

all: build

test:
# 	term "${PHP} test_language_utilities.php"
# 	term "${PHP} test.php"
	term "${PHP} ${TEST_CBLOCKS}"

build:
	term "${PHP} ${TEST_MACOS_11_0}"
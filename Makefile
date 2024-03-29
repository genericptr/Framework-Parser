COCOA_ALL_FRAMEWORKS = Foundation, CoreImage, QuartzCore, CoreData, AppKit
GROUP_FRAMEWORKS = Foundation, CoreImage, QuartzCore, CoreData, AppKit

UNIVERSAL_FRAMEWORKS = CoreFoundation, CoreGraphics
SUPPORT_FRAMEWORKS = ^Foundation, AddressBook
MACOS_11_0_ALL_FRAMEWORKS = ${COCOA_ALL_FRAMEWORKS},AddressBook,UserNotifications,UserNotificationsUI,Contacts,ContactsUI,CoreAudioKit,SensorKit,QuickLook,QuickLookUI,CoreML,CoreBluetooth,CoreHaptics,CoreWLAN,PrintCore,PushKit,MetricKit,ReplayKit,EventKit,AppleScriptObjC,Automator,CloudKit,StoreKit,CoreLocation,ImageCaptureCore,ImageKit,PDFKit,MapKit,SceneKit,SpriteKit,GameKit,GameplayKit,WebKit,AVFAudio,Photos,PhotosUI,ModelIO,Metal,MetalKit,CalendarStore,AVFoundation,QuartzCore,CoreAudio,CoreData,CoreMedia,CoreVideo,

# QuickLookThumbnailing, SecurityFoundation, SecurityInterface
# Accessibility, Accounts, PreferencePanes, AudioToolbox, VideoToolbox
# QuartzFilters, QuartzComposer, ScriptingBridge

MACOS_10_10_OBJC_FRAMEWORKS = Accounts, AudioVideoBridging, EventKit, GLKit, GameKit, SceneKit, Social, VideoToolbox, AVFoundation, AppleScriptObjC, AddressBook, Automator, CalendarStore, Collaboration, CoreAudio, CoreAudioKit, CoreImage, CoreLocation, CoreMedia, CoreMediaIO, CoreVideo, DiscRecording, DiscRecordingUI, ImageKit, PDFKit, ImageCaptureCore, InputMethodKit, IOBluetooth, IOBluetoothUI, OSAKit, PreferencePanes, Foundation, AppKit, CoreData, QuartzCore, WebKit, SyncServices, StoreKit, ServiceManagement, SecurityInterface, SecurityFoundation, QuartzFilters, QuickLook, QuickLookUI, QTKit, QuartzComposer, CFOpenDirectory, OpenDirectory, PubSub, ScreenSaver, ScriptingBridge, InstallerPlugins, InstantMessage, CoreBluetooth, AVKit, GameController, MapKit, MediaAccessibility, MediaLibrary, SpriteKit, iTunesLibrary, FinderSync, CryptoTokenKit, MultipeerConnectivity, NotificationCenter, CloudKit, LocalAuthentication
MACOS_11_0_OBJC_FRAMEWORKS = ARKit, AVFAudio, AVFoundation, AVKit, Accessibility, Accounts, AdServices, AdSupport, AddressBook, AddressBookUI, AppClip, AppKit, AppTrackingTransparency, AppleScriptObjC, AssetsLibrary, AudioToolbox, AudioVideoBridging, AuthenticationServices, AutomaticAssessmentConfiguration, Automator, BackgroundTasks, BusinessChat, CalendarStore, CallKit, CarPlay, ClassKit, ClockKit, CloudKit, Collaboration, Contacts, ContactsUI, CoreAudio, CoreAudioKit, CoreBluetooth, CoreData, CoreGraphics, CoreHaptics, CoreImage, CoreLocation, CoreMIDI, CoreML, CoreMotion, CoreNFC, CoreSpotlight, CoreVideo, CoreWLAN, CryptoTokenKit, DeviceCheck, DiscRecording, DiscRecordingUI, EventKit, EventKitUI, ExceptionHandling, ExecutionPolicy, ExternalAccessory, FileProvider, FileProviderUI, FinderSync, Foundation, GLKit, GameController, GameKit, GameplayKit, HIToolbox, HealthKit, HealthKitUI, HomeKit, IMServicePlugIn, IOSurface, IOUSBHost, IdentityLookup, IdentityLookupUI, ImageCaptureCore, ImageKit, InputMethodKit, InstallerPlugins, Intents, IntentsUI, JavaNativeFoundation, JavaRuntimeSupport, JavaScriptCore, LinkPresentation, LocalAuthentication, MLCompute, MPSCore, MPSImage, MPSMatrix, MPSNDArray, MPSNeuralNetwork, MPSRayIntersector, MapKit, MediaLibrary, MediaPlayer, MessageUI, Messages, Metal, MetalKit, MetalPerformanceShadersGraph, MetricKit, ModelIO, MultipeerConnectivity, NaturalLanguage, NearbyInteraction, NetworkExtension, NotificationCenter, OSAKit, OSLog, OpenDirectory, PDFKit, ParavirtualizedGraphics, PassKit, PencilKit, Photos, PhotosUI, PreferencePanes, PrintCore, PushKit, QuartzComposer, QuartzCore, QuartzFilters, QuickLook, QuickLookThumbnailing, QuickLookUI, ReplayKit, SafariServices, SceneKit, ScreenSaver, ScreenTime, ScriptingBridge, SecurityFoundation, SecurityInterface, SensorKit, Social, SoundAnalysis, Speech, SpriteKit, StoreKit, SyncServices, SystemExtensions, Twitter, UIKit, UniformTypeIdentifiers, UserNotifications, UserNotificationsUI, VideoSubscriberAccount, Virtualization, Vision, VisionKit, WatchConnectivity, WebKit, iAd, iTunesLibraryparser$

PHP = php7
CWD = $(shell pwd)

TEST_CBLOCKS = parser.php -header="Foundation:${CWD}/tests/cblocks.h" -macos -unit -show
TEST_10_15 = parser.php -header="CoreVideo:${CWD}/tests/changes_10_15.h" -default_framework=cocoa_base -macos -unit -show -pre
TEST_PROTOCOL_IMPORTS = parser.php -header="Foundation:${CWD}/tests/protocol_imports.h" -macos -unit -show
TEST_DISPATCH = parser.php -header="Dispatch:${CWD}/tests/dispatch.h" -macos -unit -show -pre

# macOS 11 SDK
MACOS_11_0_CORE = parser.php -frameworks=\"${COCOA_ALL_FRAMEWORKS}\" -out=~/Desktop/MacOS_11_0 -group=CocoaAll -group_frameworks=\"${GROUP_FRAMEWORKS}\" -all_units=\"@/templates/mac-unit-template.txt\" -template=\"@/templates/cocoaall-template.txt\" -skeleton=\"@/skeletons/CocoaAll_11_0\" -sdk=MacOSX11.3.sdk -default_framework=cocoa_base -macos -all -class_definitions -v -safe -build_skeletons
MACOS_11_0_SUPPORT = parser.php -frameworks=\"${SUPPORT_FRAMEWORKS}\" -out=~/Desktop/MacOS_11_0 -all_units=\"@/templates/mac-unit-template.txt\" -template=\"@/templates/cocoaall-template.txt\" -skeleton=\"@/skeletons/CocoaAll_11_0\" -sdk=MacOSX11.3.sdk -default_framework=cocoa_base -group=CocoaAll -macos -all -class_definitions -v -safe -build_skeletons
MACOS_11_0_UNIVERSAL = parser.php -frameworks=\"${UNIVERSAL_FRAMEWORKS}\" -out=~/Desktop/11_0_Universal -group=CocoaAll -all_units=\"@/templates/mac-unit-template.txt\" -template=\"@/templates/mac-unit-template.txt\" -skeleton=\"@/skeletons/CoreFoundation_11_0\" -sdk=MacOSX11.3.sdk -default_framework=carbon_base -macos -all -class_definitions -v -safe -build_skeletons
MACOS_11_0_ALL = parser.php -frameworks=\"${MACOS_11_0_ALL_FRAMEWORKS}\" -out=~/Desktop/MacOS_11_0 -group=CocoaAll -group_frameworks=\"${GROUP_FRAMEWORKS}\" -all_units=\"@/templates/mac-unit-template.txt\" -template=\"@/templates/cocoaall-template.txt\" -skeleton=\"@/skeletons/CocoaAll_11_0\" -sdk=MacOSX11.3.sdk -default_framework=cocoa_base -macos -all -class_definitions -v -safe -build_skeletons -all


TEST_DIRECTORY = parser.php -dir=\"Dispatch:/Library/Developer/CommandLineTools/SDKs/MacOSX10.15.sdk/usr/include/dispatch\" -out=\"/Users/ryanjoseph/Desktop/dispatch\" -v+ -safe -build_skeletons -plain_c

# -all_units="@/templates/mac-unit-template.txt" -template="@/templates/mac-unit-template.txt" -skeleton="@/skeletons/CoreFoundation_11_0"

all:
# 	term "${PHP} extras/test.php"
# 	term "${PHP} extras/parser_tests.php"
# 	term "php8 extras/sdk_merger.php"
# 	term "php8 extras/protocol_fixer.php"
# 	term "${PHP} ${TEST_DISPATCH}"
	term "${PHP} ${TEST_10_15}"
# 	term "${PHP} ${MACOS_11_0_CORE}"
# 	term "${PHP} ${MACOS_11_0_ALL}"
# 	term "${PHP} ${MACOS_11_0_SUPPORT}";
# 	term "${PHP} ${MACOS_11_0_UNIVERSAL}"
# 	term "${PHP} ${TEST_DIRECTORY}"

COCOA_ALL_FRAMEWORKS = Foundation, CoreImage, QuartzCore, CoreData, AppKit
GROUP_FRAMEWORKS = Foundation, CoreImage, QuartzCore, CoreData, AppKit

KIT_FRAMEWORKS = WebKit, GameKit, GameplayKit, SpriteKit, SceneKit, ImageKit, PDFKit, MapKit, StoreKit, CloudKit, PushKit, MetricKit, ReplayKit, EventKit
DEPRECATE_FRAMEWORKS = GLKit
CORE_FRAMWORKS = CoreML, CoreBluetooth, CoreHaptics, CoreWLAN, PrintCore, ImageCaptureCore, CoreLocation
UNIVERSAL_FRAMEWORKS = ^Foundation, CoreFoundation, CoreGraphics, UniformTypeIdentifiers
MEDIA_FRAMEWORKS = Photos, PhotosUI, AVFAudio, AVFoundation
METAL_FRAMEWORKS = Metal, MetalKit, ModelIO
MPS_FRAMWORKS = MLCompute, MPSCore, MPSImage, MPSMatrix, MPSNDArray, MPSNeuralNetwork, MPSRayIntersector
SUPPORT_FRAMEWORKS_ALL = ^Foundation, CoreVideo, CoreMedia, CoreAudio, CalendarStore, Automator, AppleScriptObjC
SUPPORT_FRAMEWORKS = ^Foundation, ContactsUI

# Accessibility

MACOS_10_10_OBJC_FRAMEWORKS = Accounts, AudioVideoBridging, EventKit, GLKit, GameKit, SceneKit, Social, VideoToolbox, AVFoundation, AppleScriptObjC, AddressBook, Automator, CalendarStore, Collaboration, CoreAudio, CoreAudioKit, CoreImage, CoreLocation, CoreMedia, CoreMediaIO, CoreVideo, DiscRecording, DiscRecordingUI, ImageKit, PDFKit, ImageCaptureCore, InputMethodKit, IOBluetooth, IOBluetoothUI, OSAKit, PreferencePanes, Foundation, AppKit, CoreData, QuartzCore, WebKit, SyncServices, StoreKit, ServiceManagement, SecurityInterface, SecurityFoundation, QuartzFilters, QuickLook, QuickLookUI, QTKit, QuartzComposer, CFOpenDirectory, OpenDirectory, PubSub, ScreenSaver, ScriptingBridge, InstallerPlugins, InstantMessage, CoreBluetooth, AVKit, GameController, MapKit, MediaAccessibility, MediaLibrary, SpriteKit, iTunesLibrary, FinderSync, CryptoTokenKit, MultipeerConnectivity, NotificationCenter, CloudKit, LocalAuthentication
MACOS_11_0_OBJC_FRAMEWORKS = ARKit, AVFAudio, AVFoundation, AVKit, Accessibility, Accounts, AdServices, AdSupport, AddressBook, AddressBookUI, AppClip, AppKit, AppTrackingTransparency, AppleScriptObjC, AssetsLibrary, AudioToolbox, AudioVideoBridging, AuthenticationServices, AutomaticAssessmentConfiguration, Automator, BackgroundTasks, BusinessChat, CalendarStore, CallKit, CarPlay, ClassKit, ClockKit, CloudKit, Collaboration, Contacts, ContactsUI, CoreAudio, CoreAudioKit, CoreBluetooth, CoreData, CoreGraphics, CoreHaptics, CoreImage, CoreLocation, CoreMIDI, CoreML, CoreMotion, CoreNFC, CoreSpotlight, CoreVideo, CoreWLAN, CryptoTokenKit, DeviceCheck, DiscRecording, DiscRecordingUI, EventKit, EventKitUI, ExceptionHandling, ExecutionPolicy, ExternalAccessory, FileProvider, FileProviderUI, FinderSync, Foundation, GLKit, GameController, GameKit, GameplayKit, HIToolbox, HealthKit, HealthKitUI, HomeKit, IMServicePlugIn, IOSurface, IOUSBHost, IdentityLookup, IdentityLookupUI, ImageCaptureCore, ImageKit, InputMethodKit, InstallerPlugins, Intents, IntentsUI, JavaNativeFoundation, JavaRuntimeSupport, JavaScriptCore, LinkPresentation, LocalAuthentication, MLCompute, MPSCore, MPSImage, MPSMatrix, MPSNDArray, MPSNeuralNetwork, MPSRayIntersector, MapKit, MediaLibrary, MediaPlayer, MessageUI, Messages, Metal, MetalKit, MetalPerformanceShadersGraph, MetricKit, ModelIO, MultipeerConnectivity, NaturalLanguage, NearbyInteraction, NetworkExtension, NotificationCenter, OSAKit, OSLog, OpenDirectory, PDFKit, ParavirtualizedGraphics, PassKit, PencilKit, Photos, PhotosUI, PreferencePanes, PrintCore, PushKit, QuartzComposer, QuartzCore, QuartzFilters, QuickLook, QuickLookThumbnailing, QuickLookUI, ReplayKit, SafariServices, SceneKit, ScreenSaver, ScreenTime, ScriptingBridge, SecurityFoundation, SecurityInterface, SensorKit, Social, SoundAnalysis, Speech, SpriteKit, StoreKit, SyncServices, SystemExtensions, Twitter, UIKit, UniformTypeIdentifiers, UserNotifications, UserNotificationsUI, VideoSubscriberAccount, Virtualization, Vision, VisionKit, WatchConnectivity, WebKit, iAd, iTunesLibraryparser$

PHP = php7
CWD = $(shell pwd)

TEST_CBLOCKS = parser.php -header="Foundation:${CWD}/tests/cblocks.h" -macos -unit -show
TEST_10_15 = parser.php -header="CoreVideo:${CWD}/tests/changes_10_15.h" -default_framework=cocoa_base -macos -unit -show
TEST_PROTOCOL_IMPORTS = parser.php -header="Foundation:${CWD}/tests/protocol_imports.h" -macos -unit -show

# macOS 11 SDK
MACOS_11_0_CORE = parser.php -frameworks=\"${COCOA_ALL_FRAMEWORKS}\" -out=~/Desktop/CocoaAll_11_0 -group=CocoaAll -group_frameworks=\"${GROUP_FRAMEWORKS}\" -all_units=\"@/templates/mac-unit-template.txt\" -template=\"@/templates/cocoaall-template.txt\" -skeleton=\"@/skeletons/CocoaAll_11_0\" -sdk=MacOSX11.3.sdk -default_framework=cocoa_base -macos -all -class_definitions -v -safe -build_skeletons
MACOS_11_0_SUPPORT = parser.php -frameworks=\"${SUPPORT_FRAMEWORKS}\" -out=~/Desktop/CocoaAll_11_0 -all_units=\"@/templates/mac-unit-template.txt\" -template=\"@/templates/cocoaall-template.txt\" -skeleton=\"@/skeletons/CocoaAll_11_0\" -sdk=MacOSX11.3.sdk -default_framework=cocoa_base -group=CocoaAll -macos -all -class_definitions -v -safe -build_skeletons
MACOS_11_0_UNIVERSAL = parser.php -frameworks=\"${UNIVERSAL_FRAMEWORKS}\" -out=~/Desktop/11_0_Universal -group=CocoaAll -all_units=\"@/templates/mac-unit-template.txt\" -template=\"@/templates/mac-unit-template.txt\" -skeleton=\"@/skeletons/CoreFoundation_11_0\" -sdk=MacOSX11.3.sdk -default_framework=carbon_base -macos -all -class_definitions -v -safe -build_skeletons

all:
# 	term "${PHP} extras/test.php"
# 	term "${PHP} extras/parser_tests.php"
# 	term "${PHP} ${TEST_10_15}"
# 	term "${PHP} ${MACOS_11_0_CORE}"
	term "${PHP} ${MACOS_11_0_SUPPORT}";
# 	term "${PHP} ${MACOS_11_0_UNIVERSAL}"
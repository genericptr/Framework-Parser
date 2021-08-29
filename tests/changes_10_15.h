

@protocol NSAccessibilityElement <NSObject>
- (NSRect)accessibilityFrame;
- (BOOL)isAccessibilityFocused;
@end

@protocol NSAccessibility <NSObject>
@property NSRect accessibilityFrame API_AVAILABLE(macos(10.10));
@property (getter = isAccessibilityFocused) BOOL accessibilityFocused API_AVAILABLE(macos(10.10));
@end

// TODO: both getter and setter aren't being imported for NSAccessibility property
// because NSAccessibilityElement is implemented first

@interface NSWorkspace : NSObject <NSAccessibilityElement, NSAccessibility>
@end


// TODO: & ~ should be "and not"
// this only appears in one place at NSProcessInfo.h so should we really bother?
// typedef NS_OPTIONS(uint64_t, NSActivityOptions) {
//     NSActivityUserInitiatedAllowingIdleSystemSleep = (NSActivityUserInitiated & ~NSActivityIdleSystemSleepDisabled),
// } NS_ENUM_AVAILABLE(10_9, 7_0);

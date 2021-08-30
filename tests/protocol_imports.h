

@protocol NSAccessibilityElement <NSObject>
@required
- (NSRect)accessibilityFrame;
- (nullable id)accessibilityParent;

@protocol NSColorChanging <NSObject>
- (void)changeColor:(nullable NSColorPanel *)sender;
@end

@optional
- (BOOL)isAccessibilityFocused;
- (NSString *)accessibilityIdentifier;
@end

@protocol NSAccessibility <NSObject>
@property NSRect accessibilityFrame API_AVAILABLE(macos(10.10));
@property (getter = isAccessibilityFocused) BOOL accessibilityFocused API_AVAILABLE(macos(10.10));
@end

// TODO: both getter and setter aren't being imported for NSAccessibility property
// because NSAccessibilityElement is implemented first

@interface NSWorkspace : NSObject <NSAccessibilityElement, NSAccessibility, NSColorChanging>
@end

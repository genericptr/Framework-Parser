

@protocol NSAccessibilityElement <NSObject>
@required
- (NSRect)accessibilityFrame;
@optional
- (BOOL)isAccessibilityFocused;
@end

@protocol NSAccessibility <NSObject>
@property NSRect accessibilityFrame API_AVAILABLE(macos(10.10));
@property (getter = isAccessibilityFocused) BOOL accessibilityFocused API_AVAILABLE(macos(10.10));
@end

// TODO: both getter and setter aren't being imported for NSAccessibility property
// because NSAccessibilityElement is implemented first
/*
procedure setAccessibilityFrame(newValue: NSRect); message 'setAccessibilityFrame:'; { available in macos  }
*/

@interface NSWorkspace : NSObject <NSAccessibilityElement, NSAccessibility>
@end


/*

    { Adopted protocols }
    procedure setAccessibilityFocused(newValue: objcbool); message 'setAccessibilityFocused:'; { available in macos  }
    function isAccessibilityFocused: objcbool; message 'isAccessibilityFocused'; { available in macos  }
    function accessibilityFrame: NSRect; message 'accessibilityFrame';
    function isAccessibilityFocused: objcbool; message 'isAccessibilityFocused';

*/
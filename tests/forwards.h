
// ??? the for-loop bug is causing the protocol module to skip the @class forward!!
@class NSTextField, NSPanel, NSArray, NSWindow, NSImage, NSButton, NSError;
@protocol NSAlertDelegate;

// forward declaration to external class
@class MyClassB;

// class pointer referenes should be implicit pointers
@interface MyClassC : MyClassB {
@private
	MyClassB *field1;
	MyClassC *field2;
	NSTextField *field3;
}
@public
- (void) doThis: (MyClassB *)aClass with:(MyClassC *)aClass;
@end

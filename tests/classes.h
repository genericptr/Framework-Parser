@class NSDate, NSDictionary, NSError, NSException, NSNotification;
@class NSGraphicsContext, NSImage, NSPasteboard, NSWindow;
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
@class NSDockTile;
#endif

@interface NSObject
@end

@interface MyClassA : NSObject
- (void) duplicateMethod;
@end

@interface MyClassB : MyClassA
// methods can be duplicate except for class/instance methods
- (void) duplicateMethod;
+ (void) duplicateMethod;
@end

@interface MyClassC : MyClassB {
@private;								// sections can have ; actually
	int protectedIVar; 		// should be protected in the classes namespace
	int MyClassC;					// declared type, can't do this
	struct {
		int structFieldDontProtect;
		int structFieldDontProtect;
	} myStruct;
}
@public
	
- (void) thingToDo;

// duplicate of many nested levels
- (void) duplicateMethod;

// method name which is protected
- (void) protectedIVar;

// duplicate identifiers from this class (thingToDo) and super class MyClassA (duplicateMethod)
- (void) doThis1: (int)thingToDo with:(int)duplicateMethod;

// protect ivar fields in method parameters
- (void) doThis2: (int)protectedIVar with:(int)structFieldDontProtect;

@end

// prefix class duplicates to prevent errors
@interface NSMethodConflicts
+ (NSString *)pathForResource:(NSString *)name ofType:(NSString *)ext inDirectory:(NSString *)bundlePath;
- (NSString *)pathForResource:(NSString *)name ofType:(NSString *)ext inDirectory:(NSString *)subpath;
@end

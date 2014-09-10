
@interface MyClass

// macro inside of pattern - may be impossible....
+ (void)poseAsClass:(Class)aClass DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER
#if __OBJC2__
UNAVAILABLE_ATTRIBUTE
#endif
;

/* 'idx' is a value from 0 to 3 inclusive. */
- (void)getControlPointAtIndex:(size_t)idx values:(float[2])ptr;

// block in return type? there may be function pointers also
#if NS_BLOCKS_AVAILABLE
- (id (^)(id, NSArray *, NSMutableDictionary *))expressionBlock;
#endif /* MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_6 */

// inline callbacks
- (NSArray *)sortedArrayUsingFunction1:(NSInteger (*)(id, id, void *))comparator context:(void *)context;
- (NSArray *)sortedArrayUsingFunction2:(NSInteger (*)(id, id, int, int, void *))comparator context:(void *)context;
- (NSArray *)sortedArrayUsingFunction:(NSInteger (*)(id, id, void *))comparator context:(void *)context hint:(NSData *)hint;

// the callback generated (MethodsComparator) is set an implicit pointer so
// reference it with * shouldn't append a pointer suffix
- (NSArray *)sortedArrayUsingFunction:(MethodsComparator *)comparator;

// inline blocks
- (void)enumerateObjectsUsingBlock:(void (^)(id obj, NSUInteger idx, BOOL *stop))block NS_AVAILABLE(10_6, 4_0);
- (void)enumerateObjectsWithOptions:(NSEnumerationOptions)opts usingBlock:(void (^)(id obj, NSUInteger idx, BOOL *stop))block NS_AVAILABLE(10_6, 4_0);

// class method with variable parameters which requres varargs
+ (id)arrayWithObjects:(id)firstObj, ... NS_REQUIRES_NIL_TERMINATION;

// class method
+ (NSApplication *)sharedApplication;

// method with conforms to protocol hint parameter
- (void)setDelegate:(id <NSApplicationDelegate>)anObject;

// method with conforms to protocol him return type
- (id <NSApplicationDelegate>)delegate;

// method (function) with no parameters
- (NSGraphicsContext*)context;

// array bracket in type
- (void)getPixel:(NSUInteger[])p atX:(NSInteger)x y:(NSInteger)y;

// method with IBAction null defined macro return type
- (IBAction)saveDocument:(id)sender;

// method with var/pointer to a class (implicit pointer)
- (BOOL)returnError:(NSError **)outError AVAILABLE_MAC_OS_X_VERSION_10_4_AND_LATER;

// method with var/pointer to a c cype (non-impliciti pointer)
- (BOOL)returnRange:(void **)outPtr AVAILABLE_MAC_OS_X_VERSION_10_4_AND_LATER;

- (void)getRectsBeingDrawn:(const NSRect **)rects count:(NSInteger *)count;

// method (procedure) with no parameters
- (void)unhideWithoutActivation;

// class method with multiple parameters
+ (void)detachDrawingThread:(SEL)selector toTarget:(id)target withObject:(id)argument;

// broken lines
+ (void)detachDrawingThreadBroken:	(SEL)selector
																		toTarget:(id)target
																		withObject:(id)argument;

// class method with "const id *" parameter
+ (id)arrayWithObjects:(const id *)objects count:(NSUInteger)cnt;

// method with duplicate parameter names
- (void) methodWithDuplicatesNames:(int)param param1:(int)param param2:(int)param;

- (void) methodWithNoLables: (int) param1: (CFRange *) param2: (int) param3;

// default type is "id"
- (void) methodWithNoTypes: param1 withTypeInt: param2;

@end

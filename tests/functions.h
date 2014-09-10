// inline functions

FOUNDATION_EXPORT NSArray *NSSearchPathForDirectoriesInDomains(NSSearchPathDirectory directory, NSSearchPathDomainMask domainMask, BOOL expandTilde);

#if NS_INLINE

NS_INLINE NSRange NSMakeRange(NSUInteger loc, NSUInteger len) {
    NSRange r;
    r.location = loc;
    r.length = len;
    return r;
}

NS_INLINE NSUInteger NSEventMaskFromType(NSEventType type) { return (1 << type); }

NS_INLINE long NSHostByteOrder(void) {
    return CFByteOrderGetCurrent();
}

// inline function with nested unions that will get parsed
NS_INLINE NSRect NSRectFromCGRect(CGRect cgrect) {
    union _ {NSRect ns; CGRect cg;};
    return ((union _ *)&cgrect)->ns;
}

#endif

// function with multiple parameter callbacks
APPKIT_EXTERN void NSUnamedCallbacks(const void *, (NSInteger (*)(int, void *)), (NSInteger (*)(int, void *)));

// ??? Jonas said these paremters "(NSInteger (*)(int, void *)) callback1" don't compile in C... 

//APPKIT_EXTERN void NSMultiParamCallbacks((NSInteger (*)(int, void *)) callback1, (NSInteger (*)(int, void *)) callback2);

//APPKIT_EXTERN void NSMixedCallbacks(const void *mixed, (NSInteger (*)(int, void *)) callback1, (NSInteger (*)(int, void *)) callback2);

#if NSLOG
FOUNDATION_EXPORT void NSLog(NSString *format, ...) NS_FORMAT_FUNCTION(1,2);
FOUNDATION_EXPORT void NSLogv(NSString *format, va_list args) NS_FORMAT_FUNCTION(1,0);
#endif

// struct with callback fields
typedef struct {
    NSUInteger	(*hash)(NSHashTable *table, const void *);
    BOOL	(*isEqual)(NSHashTable *table, const void *, const void *); DEPRECATED_IN_MAC_OS_X_VERSION_10_4_AND_LATER
    void	(*retain)(NSHashTable *table, const void *);
    void	(*release)(NSHashTable *table, void *);
    NSString 	*(*describe)(NSHashTable *table, const void *);
} NSHashTableCallBacks;

#if EXECPTIONS

// function pointers and references to the implicit pointer type created
typedef void NSUncaughtExceptionHandler(NSException *exception); DEPRECATED_IN_MAC_OS_X_VERSION_10_4_AND_LATER

FOUNDATION_EXPORT NSUncaughtExceptionHandler *NSGetUncaughtExceptionHandler(void); DEPRECATED_IN_MAC_OS_X_VERSION_10_4_AND_LATER
FOUNDATION_EXPORT void NSSetUncaughtExceptionHandler(NSUncaughtExceptionHandler *);

#endif

// external function with pointer to implicit type parameter
APPKIT_EXTERN void NSGetError(void **error);

// external function with inline callback
APPKIT_EXTERN void NSPerform1((NSInteger (*)(int, void *)) callback);
APPKIT_EXTERN void NSPerform2((NSInteger (*)(int, long long, unsigned int, id, id, void *)) callback);

APPKIT_EXTERN void NSPerformBlock((void (^)(id obj, NSUInteger idx, BOOL *stop)) block);

// multiple arguments
FOUNDATION_EXPORT void NSLog(NSString *format, ...);
FOUNDATION_EXPORT void NSLogv(NSString *format, va_list args);
APPKIT_EXTERN NSInteger NSRunAlertPanel(NSString *title, NSString *msgFormat, NSString *defaultButton, NSString *alternateButton, NSString *otherButton, ...);

// really long return type
APPKIT_EXTERN const unsigned long long NSReadPixel(NSPoint passedPoint);

// long parameter types
APPKIT_EXTERN void NSReadPixel(unsigned long long param1, signed int param2);

// external function with const return type
APPKIT_EXTERN const NSWindowDepth *NSAvailableWindowDepths (void);

// external function with pointer return type
APPKIT_EXTERN NSColor *NSReadPixel(NSPoint passedPoint); DEPRECATED_IN_MAC_OS_X_VERSION_10_4_AND_LATER

// external framework function
APPKIT_EXTERN BOOL NSPerformService(NSString *itemName, NSPasteboard *pboard);

// external framework function (multi-line)
APPKIT_EXTERN void NSRectFillListWithColors(	const NSRect *rects, 
																							NSColor **colors, 
																							NSInteger num);


// external function with array parameter
APPKIT_EXTERN void NSWindowList(NSInteger size, NSInteger list[]);

// external library function
extern void NSOpenGLSetOption(NSOpenGLGlobalOption pname, GLint param);

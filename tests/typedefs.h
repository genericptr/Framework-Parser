
// opaque typedefs to structs
typedef struct sth_s sth_s;
typedef struct sqh_s sqh_s;
typedef struct q_xtra q_xtra;

// duplicate/opaque structs/typedefs tests
typedef struct FSRef                    FSRef;
struct FSRef {
  UInt8               hidden[80];
};
typedef struct FSRef                    FSRef;
typedef FSRef *                         FSRefPtr;
typedef struct __FSFileSecurity*        FSFileSecurityRef;

// typedef function pointer with nested function pointer parameter
typedef NSPoint (*_NestedFunction)(NSFont *obj, SEL sel, NSUInteger (*size)(const void *item));


// typedef function pointers (with pointer)
typedef NSPoint (*_NSPositionOfGlyphMethod)(NSFont *obj, SEL sel, NSGlyph cur, NSGlyph prev, BOOL *isNominal);

// multi-line function
typedef NSPoint (*_NSPositionOfGlyphMethod_MultiLine)(	NSFont *obj, 
																							SEL sel, 
																							NSGlyph cur, 
																							NSGlyph prev, 
																							BOOL *isNominal); DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER

// typedef function pointers (type only)
typedef void NSUncaughtExceptionHandler(NSException *exception);
typedef long long NSUncaughtExceptionHandler2(NSException *exception);

// block
#if NS_BLOCKS_AVAILABLE
typedef NSComparisonResult (^NSComparator)(id obj1, id obj2);
#endif
																				
// single-word type
typedef long NSInteger;

// double-word type
typedef unsigned long NSUInteger;

// triple-word type
typedef unsigned long long NSEventMask;

// pointer to generic pointer
typedef void* NSRectPointer;	

// pointer type
typedef NSRange *NSRangePointer;

// array type
typedef NSRange NSRangeArray[10];

// array pointer type
typedef NSRange *NSRangeArrayPtr[10]; DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER

// constant pointer to struct
typedef const struct __NSAppleEventManagerSuspension* NSAppleEventManagerSuspensionID;

// struct type
typedef struct _NSZone NSZone;

// struct pointer type
typedef struct _NSModalSession *NSModalSession;

// struct type with multiple aliases (and pointer type)
typedef struct mystruct mystruct_alias1 ,const mystruct_alias2, *mystruct_alias3; DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER


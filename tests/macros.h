
// All availability macros are defined in AvailabilityMacros.h
// Target macros are defined in TargetConditionals.h
//			/Developer/Xcode3/SDKs/MacOSX10.6.sdk/usr/include/TargetConditionals.h

// AVAILABLE_MAC_OS_X_VERSION_10_6_AND_LATER
// DEPRECATED_IN_MAC_OS_X_VERSION_10_4_AND_LATER
// AVAILABLE_MAC_OS_X_VERSION_10_0_AND_LATER_BUT_DEPRECATED_IN_MAC_OS_X_VERSION_10_4
// AVAILABLE_MAC_OS_X_VERSION_10_5_AND_LATER_BUT_DEPRECATED

// nested macros

#if !defined(NS_BLOCKS_AVAILABLE)
    #if __BLOCKS__ && (MAC_OS_X_VERSION_10_6 <= MAC_OS_X_VERSION_MAX_ALLOWED || __IPHONE_4_0 <= __IPHONE_OS_VERSION_MAX_ALLOWED)
        #define FOOBAR 1
    #else
        #define FOOBAR 0
    #endif
#endif

// macros on broken lines messes up the line tracking 
// to match macros to symbols
CA_EXTERN NSString * const kCAFillModeForwards
    __OSX_AVAILABLE_STARTING (__MAC_10_5, __IPHONE_2_0);
CA_EXTERN NSString * const kCAFillModeBackwards
    __OSX_AVAILABLE_STARTING (__MAC_10_5, __IPHONE_2_0);
CA_EXTERN NSString * const kCAFillModeBoth
    __OSX_AVAILABLE_STARTING (__MAC_10_5, __IPHONE_2_0);
CA_EXTERN NSString * const kCAFillModeRemoved
    __OSX_AVAILABLE_STARTING (__MAC_10_5, __IPHONE_2_0);

// hexadecimal in macro needs to get converted
#if !defined(__MACTYPES__) || (defined(UNIVERSAL_INTERFACES_VERSION) && UNIVERSAL_INTERFACES_VERSION < 0x0340)
    typedef UInt32                  UTF32Char;
    typedef UInt16                  UTF16Char;
    typedef UInt8                   UTF8Char;
#endif

// if not defined
#ifndef CGFLOAT_DEFINED
  typedef float CGFloat;
# define CGFLOAT_MIN FLT_MIN
# define CGFLOAT_MAX FLT_MAX
# define CGFLOAT_IS_DOUBLE 0
# define CGFLOAT_DEFINED 1
#endif

#ifdef SOURCE_LEVEL_MACRO
enum {                      
    NSBoxPrimary	= ~0,			
    NSBoxSecondary	= 1,    
    NSBoxSeparator	= 2,    
    NSBoxOldStyle	= 3,    	
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
    NSBoxCustom		= 4     // gets skipped!
#endif
};
#endif

#if LONG_INT
typedef long NSInteger;
#elif LONGER_INT
typedef long long NSInteger;	
#endif

/*

#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
typedef long NSInteger;
typedef unsigned long NSUInteger;
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_6
typedef unsigned long long NSEventMask;
#endif
typedef void* NSRectPointer;	
#endif

#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
typedef struct {
	int field1;
	int field2;
} typdef_struct_name_end;
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_6
@interface MyClassB : MyClassA
- (void) duplicateMethod;
- (void) duplicateMethod;
- (void) duplicateMethod;
@end

typedef struct typdef_struct_name_beginning {
	int field1;
	int field2;
};
#endif
#endif

// only the deepest macro is applied for nested source-level
// macros
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_4
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
enum {                      
    NSBoxPrimary	= ~0,
    NSBoxSecondary	= 1, 
};
#endif
#endif

// macros in struct
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_4
typedef struct __Brflags {
	
#if __BIG_ENDIAN__
    unsigned int        field:1;
#endif

// conflict between ifdef and defined symbol __BIG_ENDIAN__
#ifdef __BIG_ENDIAN__
    unsigned int        field:1;
#endif
} _Brflags;
#endif

*/
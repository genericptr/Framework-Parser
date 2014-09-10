
// ENUMARATIONS

enum {
	enum_1_AllocationFailed						= -12760,
	enum_1_RequiredParameterMissing				= -12761,
};

enum
{
	enum_2_AllocationFailed						= -12760,
	enum_2_RequiredParameterMissing				= -12761,
};

enum {
	bitwise_operator = kCMTimeFlags_PositiveInfinity | kCMTimeFlags_NegativeInfinity | kCMTimeFlags_Indefinite
};

enum {inline_field_1 = 100, inline_field_2 = 200};

typedef enum
{
	typedef_enum_1_AllocationFailed						= -12760,
	typedef_enum_1_RequiredParameterMissing				= -12761,
} typedef_enum_1;

typedef enum {
	typedef_enum_2_AllocationFailed						= -12760,
	typedef_enum_2_RequiredParameterMissing				= -12761,
} typedef_enum_2;



// STRUCTS

// basic struct
struct base_struct_1
{
	int		field1;
	int		field2;
};

// typedef of existing struct (struct base_struct_1)
typedef struct base_struct_1 base_struct_1, base_struct_2, *base_struct_1;

// typedef struct with multiple other typedefs and pointer
typedef struct base_struct_2 {
	int		field1;
	int		field2;
} alias_struct_1, alias_struct_2, *alias_struct_1;

// pointer type of typedef struct
typedef alias_struct_1 *pointeraliasname;

typedef struct {
	int field1;
	int field2;
} typdef_struct_name_end;

typedef struct typdef_struct_name_beginning {
	int field1;
	int field2;
};

struct CATransform3D
{
 CGFloat m11, m12, m13, m14;
 CGFloat m21, m22, m23, m24;
 CGFloat m31, m32, m33, m34;
 CGFloat m41, m42, m43, m44;
};

typedef struct CATransform3D CATransform3D;

typedef const struct __CTLine * CTLineRef;

// variable definitions
//struct { <struct_definition> } structvar; // same as "var structvar: record <struct_definition> end;"
//typedef struct { <struct_definition> } aliasname; // same as (***) above, except that you can only use "aliasname" to refer to this type, rather than also "struct structname"


// HEXADECIMAL DEFINES

#define kCMTimeMaxTimescale 0x7fffffffL

// MULTI-LINE FUNCTION POINTERS

CM_EXPORT
Boolean CMFormatDescriptionEqual(
	CMFormatDescriptionRef ffd1,	/*! @param ffd1
									The first formatDescription. */
	CMFormatDescriptionRef ffd2)	/*! @param ffd2
									The second formatDescription. */
							__OSX_AVAILABLE_STARTING(__MAC_10_7,__IPHONE_4_0);
							
// MULTI-LINE EXTERNAL FUNCTIONS

CM_EXPORT 
CMTime CMTimeMake(
				int64_t value,		/*! @param value		Initializes the value field of the resulting CMTime. */
				int32_t timescale)	/*! @param timescale	Initializes the timescale field of the resulting CMTime. */
							__OSX_AVAILABLE_STARTING(__MAC_10_7,__IPHONE_4_0);

// CALLBACKS

typedef void (*ABExternalChangeCallback)(ABAddressBookRef addressBook, CFDictionaryRef info, void *context);

// INLINE FUNCTIONS

NS_INLINE NSSwappedFloat NSConvertHostFloatToSwapped(float x) {
   union fconv {
	float number;
	NSSwappedFloat sf;
   };
   return ((union fconv *)&x)->sf;
}

// CLASSES

// Multi-line @interface
@interface NSWindow : NSResponder
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
<NSAnimatablePropertyContainer, NSUserInterfaceValidations>
#else
<NSUserInterfaceValidations>
#endif
{
   NSRect              _frame;
}

+ (NSRect)frameRectForContentRect:(NSRect)cRect styleMask:(NSUInteger)aStyle;
@end

/*!
    clean multi-line comments
*/
@interface NSHTTPCookie : NSObject
{
@private
    NSHTTPCookieInternal * _cookiePrivate;
}

/*!
    <table border=1 cellspacing=2 cellpadding=4>
*/

@end

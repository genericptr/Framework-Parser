
// auto load external macros from defines that are "extern"
// also include inlines in the list "static __inline__"
#define CFN_EXPORT extern "C"
#define CFN_EXPORT extern
#define CF_INLINE static __inline__

// floating points
#define CL_FLT_MAX          340282346638528859811704183484516925440.0f
#define CL_FLT_MIN          1.175494350822287507969e-38f
#define NPY_Ef        2.718281828459045235360287471352662498F
#define FLT_MAX 3.402823466E+38F

// define to already defined type
// how do we know this are not opaque?
#define NSNormalWindowLevel              kCGNormalWindowLevel

// integer define (decimal)
#define NSAppKitVersionNumberWithDeferredWindowDisplaySupport		1019.0

// integer define (simple integer)
#define NSGEOMETRY_TYPES_SAME_AS_CGGEOMETRY_TYPES 1

// hexadecimal define
#define HexDefine		0x0009900

// string value
#define ASL_KEY_REF_PROC    "RefProc"

// bitwise define
// are these allowed in c?
#define BitwiseDefine		(NSTerminateCancel | NSTerminateNow | NSTerminateLater)

// single word in parenthesis suggests an inline function
// although technically this could be a constant
#define NS_FORMAT_ARGUMENT(A)

// shl define
#define kABMultiValueMask (1 << 8)

// integer define with parenthesis
#define	NSVariableStatusItemLength	(-1)

#define	NSVariableStatusItemLength (asdsad xsdds, sdfsdf,)

// null macro
#define IBOutlet

// string macro
// simple string macro
#define IBAction void

// complex
// these are too complex for us and most likely can't be translated anyways
#define CORE_IMAGE_EXPORT extern "C" __attribute__((visibility("default")))
#define NS_DURING		@try {
#define NS_CLASS_AVAILABLE (_mac, _ios)
# define CA_EXTERN_C_BEGIN extern "C" {
# define CA_EXTERN_C_END   }
#  define CA_INLINE static inline
#define CA_OS_VERSION(m, i) (MAC_OS_X_VERSION_MIN_REQUIRED >= (m))

// inline define
// these should be ignored
#define NSGlyphInfoAtIndex(IX) ((NSTypesetterGlyphInfo *)((void *)glyphs + (sizeOfGlyphInfo * IX)))

// multi-line inline define?
#define NSLocalizedStringWithDefaultValue(key, tbl, bundle, val, comment) \
	    [bundle localizedStringForKey:(key) value:(val) table:(tbl)]

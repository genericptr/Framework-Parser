
// how is Boolean defined? this can't be!
typedef unsigned char           Boolean;
typedef int          						integer;

DIE_NOW

// pointer is explicit so why is it getting added?
extern OSStatus	MIDIEndpointGetRefCons(void **ref1, NSError **ref2);

DIE_NOW

// we need to to change UInt8Ptr for type compatibility with universal interfaces
CF_EXPORT CFDataRef CFDataCreate1(CFAllocatorRef allocator, const UInt8 *bytes, CFIndex length);
CF_EXPORT CFDataRef CFDataCreate2(CFAllocatorRef allocator, UInt8 bytesCount, CFIndex length);

// what is ~0UL?
typedef NS_OPTIONS(NSUInteger, UIRectCorner) {
    UIRectCornerTopLeft     = 1 << 0,
    UIRectCornerTopRight    = 1 << 1,
    UIRectCornerBottomLeft  = 1 << 2,
    UIRectCornerBottomRight = 1 << 3,
    UIRectCornerAllCorners  = ~0UL
};

// can't define this!
// UIKitDefines
#define __has_feature(x) 0

DIE_NOW

// strip line breaks from macros
CG_EXTERN CGRect CGPDFDocumentGetMediaBox(CGPDFDocumentRef document, int page)
    CG_AVAILABLE_BUT_DEPRECATED(__MAC_10_0, __MAC_10_5,
	__IPHONE_NA, __IPHONE_NA);

CG_EXTERN CGRect CGPDFDocumentGetCropBox(CGPDFDocumentRef document, int page)
    CG_AVAILABLE_BUT_DEPRECATED(__MAC_10_0, __MAC_10_5,
	__IPHONE_NA, __IPHONE_NA);


typedef CF_ENUM(CFIndex, CFLocaleLanguageDirection) {
    kCFLocaleLanguageDirectionUnknown = 0,
    kCFLocaleLanguageDirectionLeftToRight = 1,
    kCFLocaleLanguageDirectionRightToLeft = 2,
    kCFLocaleLanguageDirectionTopToBottom = 3,
    kCFLocaleLanguageDirectionBottomToTop = 4
};

// ??? where's the availability macro??
CF_EXPORT
uint32_t CFLocaleGetWindowsLocaleCodeFromLocaleIdentifier(CFStringRef localeIdentifier) CF_AVAILABLE(10_6, 4_0);

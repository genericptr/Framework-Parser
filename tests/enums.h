

#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_3
enum {
    NSFontPanelFaceModeMask = 1 << 0,
    NSFontPanelSizeModeMask = 1 << 1,
    NSFontPanelCollectionModeMask = 1 << 2,
#endif
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_4
    NSFontPanelUnderlineEffectModeMask = 1<<8,
    NSFontPanelStrikethroughEffectModeMask = 1<<9,
    NSFontPanelTextColorEffectModeMask = 1<< 10,
    NSFontPanelDocumentColorEffectModeMask = 1<<11,
    NSFontPanelShadowEffectModeMask = 1<<12,
    NSFontPanelAllEffectsModeMask = 0XFFF00,
#endif
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_3
    NSFontPanelStandardModesMask = 0xFFFF,
    NSFontPanelAllModesMask = 0xFFFFFFFF
};
#endif

// hexadecimal shl and hexadecimal with integer hint
enum {
    NSTextCheckingAllSystemTypes    = 0xffffffffULL,        // the first 32 types are reserved
    NSTextCheckingAllCustomTypes    = 0xffffffffULL << 32,  // clients may use the remainder for their own purposes
    NSTextCheckingAllTypes          = (NSTextCheckingAllSystemTypes | NSTextCheckingAllCustomTypes)
};

enum {                      
    NSBoxPrimary	= ~0,			
    NSBoxSecondary	= 1,    
    NSBoxSeparator	= 2,    
    NSBoxOldStyle	= 3,    	
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
    NSBoxCustom		= 4     // gets skipped!
#endif
};

// integer values in bitwise or
enum {
    NSXMLNodePreserveAll = (
            NSXMLNodePreserveCharacterReferences |
            0xFFF00000 |
						100UL |
						(1 <<  0) |
						)
};

// auto-increment with default defined in the middle
typedef enum {
	NSOpenGLGOFormatCacheSize,						/* Set the size of the pixel format cache        */
	NSOpenGLGOClearFormatCache,						/* Reset the pixel format cache if true          */
	NSOpenGLGORetainRenderers  = 500,			/* Whether to retain loaded renderers in memory  */
	NSOpenGLGOResetLibrary,
} NSOpenGLGlobalOption;


// auto index
enum {
    NSAnimationBlocking,
    NSAnimationNonblocking,
    NSAnimationNonblockingThreaded
};

// indexed enum
enum {
    NSWarningAlertStyle = 0,
    NSInformationalAlertStyle = 1,
    NSCriticalAlertStyle = 2
};

// auto index with start index
enum {
    NSScaleProportionally = 100, 	// Deprecated. Use NSScaleProportionallyDown
    NSScaleToFit,              		// Deprecated. Use NSScaleAxesIndependently
    NSScaleNone                		// Deprecated. Use NSImageScaleNone
};

// broken lines
enum {
    NSBrokenLine1 ,
    NSBrokenLine2
    , NSBrokenLine3
};

enum {
    NSBoxPrimary	= 0,	// group subviews with a standard look. default
    NSBoxSecondary	= 1,    // same as primary since 10.3
    NSBoxSeparator	= 2,    // vertical or horizontal separtor line.  Not used with subviews.
    NSBoxOldStyle	= 3,    // 10.2 and earlier style boxes
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
    NSBoxCustom		= 4     // draw based entirely on user parameters, not human interface guidelines
#endif
};

// single line
enum {NSAnimationBlocking,NSAnimationNonblocking,NSAnimationNonblockingThreaded};

// macros
enum {
    NSDeprecatedEnum1 DEPRECATED_IN_MAC_OS_X_VERSION_10_4_AND_LATER,
    NSDeprecatedEnum2 AVAILABLE_MAC_OS_X_VERSION_10_0_AND_LATER_BUT_DEPRECATED_IN_MAC_OS_X_VERSION_10_4,

#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_3
    NSDeprecatedEnum3,
		NSDeprecatedEnum4,
#endif
};

#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_4
typedef enum _NSEnumWithVersion {
	NSEnumWithVersionField = 1
} NSEnumWithVersion;
#endif

// no comma at end
enum _NSGlyphRelation {
    NSGlyphBelow = 1,
    NSGlyphAbove = 2
};

typedef enum _NSLayoutDirection {
    NSLayoutLeftToRight = 0,
    NSLayoutRightToLeft
} NSLayoutDirection;

enum {
   kABInvalidPropertyType         = 0x0,
   kABStringPropertyType          = 0x1,
   kABIntegerPropertyType         = 0x2,
   kABRealPropertyType            = 0x3,
   kABDateTimePropertyType        = 0x4,
   kABDictionaryPropertyType      = 0x5,
   kABMultiStringPropertyType     = kABMultiValueMask | kABStringPropertyType,
   kABMultiIntegerPropertyType    = kABMultiValueMask | kABIntegerPropertyType,
   kABMultiRealPropertyType       = kABMultiValueMask | kABRealPropertyType,
   kABMultiDateTimePropertyType   = kABMultiValueMask | kABDateTimePropertyType,
   kABMultiDictionaryPropertyType = kABMultiValueMask | kABDictionaryPropertyType,
};

// type hints
enum {
  kCFragAllFileTypes            = (long)0xFFFFFFFF,
  kDataBrowserItemAnyState      = (unsigned long)(-1),
  kLSRolesAll      = (UInt32)(-1),
  kEnumValueIntegerWithParenthesis			= (-1000),
};

// # characters break generic field and are captured by string pattern
enum {
  gestaltScriptCount            = 'scr#' 
	kControlTabListResType        = 'tab#'
	pInherits                     = 'c@#^',
};

// various value kinds
enum {
	UIRectCornerAllCorners  = ~0,
  kEnumValueHexadecimal         = 0x000c,
  kEnumValueHexadecimalUL1    = 0xffff0000UL, 
  kEnumValueHexadecimalUL2    = 0x66660000UL, 
  kEnumValueString         = "string",
  kEnumValueInteger         = 100,
  kEnumValueNegativeIntegerWithHint = -100L,
  kEnumValueSHL         = (1 <<  0),
  kEnumValueSHLWithModifier1            = 1U << 100,	
  kEnumValueSHLWithModifier2            = 1UL << 100,	
  kEnumValueSHLWithWord            = 1 << kEnumValueHexadecimal,
  kEnumValueIntegerWithParenthesis			= (-1000),
  kEnumValueDependentType         = kWeNeedThisType, 
  kEnumValueBitwiseOR         = kEnumValueHexadecimal | kEnumValueInteger | kEnumValueSHL,
};

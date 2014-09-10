

struct  CMIOHardwarePlugInInterface
{

    void*       _reserved;
		
    HRESULT
    (STDMETHODCALLTYPE *QueryInterface)(    void*   self,
                                            REFIID  uuid,
                                            LPVOID* interface);

    ULONG
    (STDMETHODCALLTYPE *AddRef)(    void*   self);

    ULONG
    (STDMETHODCALLTYPE *Release)(   void*   self);
		
    OSStatus
    (*Initialize)(  CMIOHardwarePlugInRef  self);

    OSStatus
    (*InitializeWithObjectID)(  CMIOHardwarePlugInRef  self,
                                CMIOObjectID           objectID);
		
    OSStatus
    (*Teardown)(    CMIOHardwarePlugInRef  self);

    void
    (*ObjectShow)(  CMIOHardwarePlugInRef  self,
                    CMIOObjectID           objectID);    
};


// these failed to get removed in the batch parse but work here
/*
	CoreVideo got finalized before CoreMedia suggesting they didn't have a dependency
*/
#define kCMFormatDescriptionExtension_CleanAperture				kCVImageBufferCleanApertureKey					// CFDictionary containing the following four keys
#define kCMFormatDescriptionKey_CleanApertureWidth				kCVImageBufferCleanApertureWidthKey				// CFNumber
#define kCMFormatDescriptionKey_CleanApertureHeight				kCVImageBufferCleanApertureHeightKey			// CFNumber
#define kCMFormatDescriptionKey_CleanApertureHorizontalOffset	kCVImageBufferCleanApertureHorizontalOffsetKey	// CFNumber
#define kCMFormatDescriptionKey_CleanApertureVerticalOffset		kCVImageBufferCleanApertureVerticalOffsetKey	// CFNumber

/*

@interface NSObject (NSDeprecated)
@end

@interface NSObject (NSDeprecated)
@end

@interface NSObject (NSDeprecated)
@end


@interface CALayer (MY_CATATEGORY1)
@end

@interface CALayer (MY_CATATEGORY2)
@end

@interface CALayer (MY_CATATEGORY3)
@end

// category protection failed
@interface CALayer (CAConstraintLayoutManager)
@property(copy) NSArray *constraints;
- (void)addConstraint:(CAConstraint *)c;
@end

@interface CAConstraintLayoutManager : NSObject
+ (id)layoutManager;
@end

// conflict with class and category
@interface QCComposition (QCCompositionRepository)
- (NSString*) identifier;
@end

@interface QCCompositionRepository : NSObject
{
@private
    dispatch_queue_t			cq;	
}
+ (QCCompositionRepository*) sharedCompositionRepository;
- (QCComposition*) compositionWithIdentifier:(NSString*)identifier;
- (NSArray*) compositionsWithProtocols:(NSArray*)protocols andAttributes:(NSDictionary*)attributes;
- (NSArray*) allCompositions;
@end

*/

@protocol NSObject

- (Class)superclass;
- (Class)class;

@end

@interface NSObject <NSObject> {
    Class	isa;
}

+ (Class)superclass;
+ (Class)class;

@end

// we can't prefix ivars anymore so protect the methods properly
@interface FOO {
	int	x, y, z, z;
}

- (Class)x;
- (Class)y;
- (Class)z;

@end

// macros in strings!
extern NSString * const EXTERNAL_STRING_CONST_A	AVAILABLE_MAC_OS_X_VERSION_10_7_AND_LATER;
extern NSString * const EXTERNAL_STRING_CONST_B	AVAILABLE_MAC_OS_X_VERSION_10_7_AND_LATER;
extern NSString * const EXTERNAL_STRING_CONST_C	AVAILABLE_MAC_OS_X_VERSION_10_7_AND_LATER;


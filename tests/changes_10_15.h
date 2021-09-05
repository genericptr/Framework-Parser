

// ✅ TOOD: pointers in generic params needs to be cleaned
// typedef NSArray<NSCollectionLayoutGroupCustomItem*> MyArray;
// typedef NSArray<NSDictionary<NSString *, id> *> ArrayOfDict;
// typedef NSArray<NSCollectionLayoutGroupCustomItem*> * _Nonnull(^NSCollectionLayoutGroupCustomItemProvider)(id<NSCollectionLayoutEnvironment> layoutEnvironment);

// TODO: conflict with properties/generics
// NSLayoutAnchor, NSLayoutXAxisAnchor *Protocol
// @interface NSLayoutXAxisAnchor : NSLayoutAnchor<NSLayoutXAxisAnchor *>
// @end


// ✅ TODO: generics in private fields
@interface GKGameSession : NSObject {
    // note the scope pattern is the problem here!
    NSMutableDictionary<NSString*, NSArray<NSNumber*> *> *_playerStates;
}

// ✅ TODO: this has 2 blocks but only one is printed! probably because of a name conflict
- (void)performBatchUpdates:(void (^)(void))updates completionHandler:(void (^)(BOOL finished))completionHandler;

@end

/*

// ✅ TODO: broken bit field struct
@interface AMWorkflowView : NSView {
    @private
    struct __AMWorkflowViewFlags
    {
        NSUInteger ignoreSubviewFrameChanges : 1;
        NSUInteger editingEnabled : 1;
        NSUInteger reserved : 30;
    } _flags;
    NSUInteger _draggingIndex;
    NSRect _selectionRect;

    id _future[4];
}
@end

// TODO: SKColor is a typedef to a defined class so "SKColor *" should not be SKColorPtr
#define SKColor NSColor
OS_EXPORT SKColor * const MyColor;

// TODO: ~0UL fails
typedef NS_OPTIONS(NSUInteger, SCNPhysicsCollisionCategory) {
    SCNPhysicsCollisionCategoryDefault = 1 << 0,    // default collision group for dynamic and kinematic objects
    SCNPhysicsCollisionCategoryStatic  = 1 << 1,    // default collision group for static objects
    SCNPhysicsCollisionCategoryAll     = ~0UL       // default for collision mask
} API_AVAILABLE(macos(10.10));

// ✅ TODO: nested structs/enums in classes and categories aren't included in unit
@interface NSNestedStructs : NSObject {
@private
    AVSampleCursorInternal  *_sampleCursor;
}

- (int64_t)stepInDecodeOrderByCount:(int64_t)stepCount;

typedef NSCollectionViewItem *  (^NSCollectionViewDiffableDataSourceItemProvider)(NSCollectionView * , NSIndexPath * , ItemIdentifierType);

typedef struct {
    BOOL      sampleIsFullSync;
    BOOL      sampleIsPartialSync;
    BOOL      sampleIsDroppable;
} AVSampleCursorSyncInfo;

typedef NS_ENUM(NSInteger, CBManagerAuthorization) {
    CBManagerAuthorizationNotDetermined = 0,
    CBManagerAuthorizationRestricted,
    CBManagerAuthorizationDenied,
    CBManagerAuthorizationAllowedAlways
} NS_ENUM_AVAILABLE(10_15, 13_0);

@end

// ✅ TODO: generic token is added into cblock type
@interface NSArray<ObjectType> (NSExtendedArray)
- (NSOrderedCollectionDifference<id> *)differenceByTransformingChangesWithBlock:(NSOrderedCollectionChange<id> *(^)(NSOrderedCollectionChange<ObjectType> *))block;
// - (NSArray<ObjectType> *)arrayByAddingObject:(ObjectType)anObject;
// - (NSArray<ObjectType> *)sortedArrayUsingFunction:(NSInteger (NS_NOESCAPE *)(ObjectType, ObjectType, void * _Nullable))comparator context:(nullable void *)context;
// - (void)enumerateObjectsUsingBlock:(void (NS_NOESCAPE ^)(ObjectType obj, NSUInteger idx, BOOL *stop))block API_AVAILABLE(macos(10.6), ios(4.0), watchos(2.0), tvos(9.0));
@end

@protocol NSSecureCoding <NSCoding>
@property (class, readonly) BOOL supportsSecureCoding;
@property (copy) NSDictionary<NSString *, NSArray<NSString *> *> *languageMap;
@end

@protocol NSCopying <NSCoding>
@property (readonly) BOOL supportsSecureCoding;
@end


@interface NSError : NSObject <NSCopying, NSSecureCoding>
@end

// ✅ TODO: generic param in callback params
@interface NSCollectionViewDiffableDataSource<SectionIdentifierType,ItemIdentifierType> : NSObject
+ (void)setUserInfoValueProviderForDomain:(NSErrorDomain)errorDomain provider:(id _Nullable (^ _Nullable)(NSError *err, NSErrorUserInfoKey userInfoKey))provider;
typedef NSCollectionViewItem *  (^NSCollectionViewDiffableDataSourceItemProvider)(NSCollectionView * , NSIndexPath * , ItemIdentifierType);
// @property(nonatomic,readonly) NSArray<SectionIdentifierType> *sectionIdentifiers;
// @property (readonly, copy) NSDictionary<NSString *, NSArray<NSString *> *> *languageMap;
// - (NSRange)spellServer:(NSSpellServer *)sender details:(NSArray<SectionIdentifierType *>) details;
@end

// ✅ TODO: generic param in callback params
@interface NSCallback<CandidateType> : NSObject
// typedef NSCollectionViewItem *  (^NSCollectionViewDiffableDataSourceItemProvider)(NSCollectionView * , NSIndexPath * , ItemIdentifierType);
// @property(nonatomic,readonly) NSArray<SectionIdentifierType> *sectionIdentifiers;
// @property (readonly, copy) NSDictionary<NSString *, NSArray<NSString *> *> *languageMap;
// - (NSRange)spellServer:(NSSpellServer *)sender details:(NSArray<SectionIdentifierType *>) details;
@property (nullable, copy) NSAttributedString * (^attributedStringForCandidate)(CandidateType candidate, NSInteger index) API_UNAVAILABLE(macCatalyst);

@end

// TODO: pointers in generic params makes: NSArray *>Ptr
// function spellServer_checkGrammarInString_language_details (sender: NSSpellServerPtr; stringToCheck: NSStringPtr; language: NSStringPtr; details: NSArray *>Ptr): NSRange; message 'spellServer:checkGrammarInString:language:details:'; { unavailable in ios, watchos, tvos }
// @protocol NSSpellServerDelegate <NSObject>
// @property (readonly, copy) NSDictionary<NSString *, NSArray<NSString *> *> *languageMap;
// - (NSRange)spellServer:(NSSpellServer *)sender checkGrammarInString:(NSString *)stringToCheck language:(nullable NSString *)language details:(NSArray<NSDictionary<NSString *, id> *> *  * )details;
// @end

// TODO: ifdef causes duplicates

#if __OBJC2__
@protocol NSItemProviderReading <NSObject>
@end
@protocol NSItemProviderWriting <NSObject>
@end
#else
@protocol NSItemProviderReading <NSObject>
@end
@protocol NSItemProviderWriting <NSObject>
@end
#endif

// TODO: & ~ should be "and not"
// this only appears in one place at NSProcessInfo.h so should we really bother?
// typedef NS_OPTIONS(uint64_t, NSActivityOptions) {
//     NSActivityUserInitiatedAllowingIdleSystemSleep = (NSActivityUserInitiated & ~NSActivityIdleSystemSleepDisabled),
// } NS_ENUM_AVAILABLE(10_9, 7_0);

*/
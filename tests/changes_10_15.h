

// TOOD: pointers in generic params needs to be cleaned
typedef NSArray<NSCollectionLayoutGroupCustomItem*> MyArray;
typedef NSArray<NSDictionary<NSString *, id> *> ArrayOfDict;

// TODO: if the value is "" then use a different pattern to make this cleaner
//   NSPlainFileType: NSString deprecated 'in "", '; cvar; external;
APPKIT_EXTERN NSString * NSPlainFileType API_DEPRECATED("", macos(10.0,10.6));
APPKIT_EXTERN NSString * NSDirectoryFileType API_DEPRECATED("", macos(10.0,10.6));


// TODO: generic param in callback params
@interface NSCollectionViewDiffableDataSource<SectionIdentifierType,ItemIdentifierType> : NSObject<NSCollectionViewDataSource>
@property(nonatomic,readonly) NSArray<SectionIdentifierType> *sectionIdentifiers;
- (instancetype)initWithCollectionView:(NSCollectionView*)collectionView itemProvider:(NSCollectionViewDiffableDataSourceItemProvider)itemProvider;
@end

// TODO: pointers in generic params makes: NSArray *>Ptr
// function spellServer_checkGrammarInString_language_details (sender: NSSpellServerPtr; stringToCheck: NSStringPtr; language: NSStringPtr; details: NSArray *>Ptr): NSRange; message 'spellServer:checkGrammarInString:language:details:'; { unavailable in ios, watchos, tvos }
@protocol NSSpellServerDelegate <NSObject>
@property (readonly, copy) NSDictionary<NSString *, NSArray<NSString *> *> *languageMap;
- (NSRange)spellServer:(NSSpellServer *)sender checkGrammarInString:(NSString *)stringToCheck language:(nullable NSString *)language details:(NSArray<NSDictionary<NSString *, id> *> * _Nullable * _Nullable)details API_AVAILABLE(macos(10.5)) API_UNAVAILABLE(ios, watchos, tvos);
@end

// TODO: conflict with properties/generics
// NSLayoutAnchor, NSLayoutXAxisAnchor *Protocol
@interface NSLayoutXAxisAnchor : NSLayoutAnchor<NSLayoutXAxisAnchor *>
@end

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

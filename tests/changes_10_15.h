

@protocol NSAccessibilityElement <NSObject>
- (NSRect)accessibilityFrame;
- (BOOL)isAccessibilityFocused;
@end

@protocol NSAccessibility <NSObject>
@property NSRect accessibilityFrame API_AVAILABLE(macos(10.10));
@property (getter = isAccessibilityFocused) BOOL accessibilityFocused API_AVAILABLE(macos(10.10));
@end


// TODO: both getter and setter aren't being imported for NSAccessibility property
// because NSAccessibilityElement is implemented first

@interface NSWorkspace : NSObject <NSAccessibilityElement, NSAccessibility>
@end

// TODO: constants are missing by why???      
// FOUNDATION_EXPORT NSURLUbiquitousSharedItemRole const NSURLUbiquitousSharedItemRoleOwner       API_AVAILABLE(macosx(10.12), ios(10.0)) API_UNAVAILABLE(watchos, tvos); // the current user is the owner of this shared item.
// FOUNDATION_EXPORT NSURLUbiquitousSharedItemRole const NSURLUbiquitousSharedItemRoleParticipant API_AVAILABLE(macosx(10.12), ios(10.0)) API_UNAVAILABLE(watchos, tvos); // the current user is a participant of this shared item.

// broken c-blocks
// @interface NSWorkspace : NSObject
// - (void)recycleURLs:(NSArray<NSURL *> *)URLs completionHandler:(void (^ _Nullable)(NSDictionary<NSURL *, NSURL *> * newURLs, NSError * _Nullable error))handler API_AVAILABLE(macos(10.6));
// - (void)duplicateURLs:(NSArray<NSURL *> *)URLs completionHandler:(void (^ _Nullable)(NSDictionary<NSURL *, NSURL *> * newURLs, NSError * _Nullable error))handler API_AVAILABLE(macos(10.6));
// @end

// TODO: broken c-block in return type
// @interface NSError : NSObject
// + (NSExpression *)expressionForBlock:(id (^)(id _Nullable evaluatedObject, NSArray<NSExpression *> *expressions, NSMutableDictionary * _Nullable context))block arguments:(nullable NSArray<NSExpression *> *)arguments API_AVAILABLE(macos(10.6), ios(4.0), watchos(2.0), tvos(9.0)); // Expression that invokes the block with the parameters; note that block expressions are not encodable or representable as parseable strings.
// + (id _Nullable (^ _Nullable)(NSError *err, NSErrorUserInfoKey userInfoKey))userInfoValueProviderForDomain:(NSErrorDomain)errorDomain API_AVAILABLE(macos(10.11), ios(9.0), watchos(2.0), tvos(9.0));
// @end

// TODO: broken c-block in params
// @interface NSExpression : NSObject
// + (NSExpression *)expressionForBlock:(id (^)(id _Nullable evaluatedObject, NSArray<NSExpression *> *expressions, NSMutableDictionary * _Nullable context))block arguments:(nullable NSArray<NSExpression *> *)arguments API_AVAILABLE(macos(10.6), ios(4.0), watchos(2.0), tvos(9.0)); // Expression that invokes the block with the parameters; note that block expressions are not encodable or representable as parseable strings.
// @end


// TODO: broken c-block caused by generic NSOrderedCollectionChange<ObjectType> 
// @interface NSOrderedCollectionDifference<ObjectType> : NSObject <NSFastEnumeration>
// - (NSOrderedCollectionDifference<id> *)differenceByTransformingChangesWithBlock:(NSOrderedCollectionChange<id> *(NS_NOESCAPE ^)(NSOrderedCollectionChange<ObjectType> *))block;
// @end


// TODO: & ~ should be "and not"
// this only appears in one place at NSProcessInfo.h so should we really bother?
// typedef NS_OPTIONS(uint64_t, NSActivityOptions) {
//     NSActivityUserInitiatedAllowingIdleSystemSleep = (NSActivityUserInitiated & ~NSActivityIdleSystemSleepDisabled),
// } NS_ENUM_AVAILABLE(10_9, 7_0);

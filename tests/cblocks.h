

// https://developer.apple.com/library/archive/documentation/Cocoa/Conceptual/ProgrammingWithObjectiveC/WorkingwithBlocks/WorkingwithBlocks.html

/* 
  
  reference to procedure; cdecl; cblock;
  
  double (^multiplyTwoValues)(double, double);

  completionHandler:(void (^ _Nullable)(NSDictionary<NSURL *, NSURL *> * newURLs, NSError * _Nullable error))
*/

// typedef void (^AVAudioNodeCompletionHandler)(void);

// typedef void (*MIDICompletionProc)(MIDISysexSendRequest *request);

// @interface NSInlineCallbacks : NSObject
// - (NSArray *)sortedArrayUsingFunction1:(NSInteger (*)(id a, id b, void * c))comparator context:(void *)context;
// - (NSArray *)sortedArrayUsingFunction2:(NSInteger (*)(id, id, int, int, void *))comparator context:(void *)context;
// @end

// broken c-blocks
@interface NSWorkspace : NSObject
- (void)recycleURLs:(NSArray<NSURL *> *)URLs completionHandler:(void (^ _Nullable)(NSDictionary<NSURL *, NSURL *> * newURLs, NSError * _Nullable error))handler API_AVAILABLE(macos(10.6));
- (void)duplicateURLs:(NSArray<NSURL *> *)URLs completionHandler:(void (^ _Nullable)(NSDictionary<NSURL *, NSURL *> * newURLs, NSError * _Nullable error))handler API_AVAILABLE(macos(10.6));
@end



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



// https://developer.apple.com/library/archive/documentation/Cocoa/Conceptual/ProgrammingWithObjectiveC/WorkingwithBlocks/WorkingwithBlocks.html

/* 
  
  reference to procedure; cdecl; cblock;
  
  1) void = return type
  2) (^simpleBlock) = name (can be anonymous)
  3) (void) = params
  void (^simpleBlock)(void) = ^{
    ...
  };
  simpleBlock();

  double (^multiplyTwoValues)(double, double);

  completionHandler:(void (^ )(NSDictionary<NSURL *, NSURL *> * newURLs, NSError *  error))
*/

// typedef void (^AVAudioNodeCompletionHandler)(void);
// typedef void (*MIDICompletionProc)(MIDISysexSendRequest *request);

// @interface NSInlineCallbacks : NSObject
// - (NSArray *)sortedArrayUsingFunction1:(NSInteger (*)(id a, id b, void * c))comparator context:(void *)context;
// - (NSArray *)sortedArrayUsingFunction2:(NSInteger (*)(id, id, int, int, void *))comparator context:(void *)context;
// - (NSArray *)sortedArrayUsingFunction3:(NSInteger (*)(NSInlineCallbacks))comparator context:(void *)context;
// @end

// broken c-blocks
@interface NSWorkspace : NSObject

+ (nullable void (^)(void))completionBlock;
+ (void)setCompletionBlock:(nullable void (^)(void))block;

// - (void)recycleURLs:(NSArray<NSURL *> *)URLs completionHandler:(void (^ )(NSDictionary<NSURL *, NSURL *> * newURLs, NSError *  error))handler;
// - (void)duplicateURLs:(NSArray<NSURL *> *)URLs completionHandler:(void (^ )(NSDictionary<NSURL *, NSURL *> * newURLs, NSError *  error))handler;
// - (void)registerDataRepresentationForTypeIdentifier:(NSString *)typeIdentifier
//                                          visibility:(NSItemProviderRepresentationVisibility)visibility
//                                         loadHandler:(NSProgress * (^)(void (^completionHandler)(NSData * data, NSError * error)))loadHandler;
@end

// TODO: generic token is added into cblock type
// @interface NSArray<ObjectType> (NSExtendedArray)
// - (NSArray<ObjectType> *)arrayByAddingObject:(ObjectType)anObject;
// - (NSArray<ObjectType> *)sortedArrayUsingFunction:(NSInteger (NS_NOESCAPE *)(ObjectType, ObjectType, void * ))comparator context:(nullable void *)context;
// - (void)enumerateObjectsUsingBlock:(void (NS_NOESCAPE ^)(ObjectType obj, NSUInteger idx, BOOL *stop))block;
// @end

// TODO: broken c-block in return type
// @interface NSError : NSObject
// + (id _Nullable (^ _Nullable)(NSError *err, NSErrorUserInfoKey userInfoKey))userInfoValueProviderForDomain:(NSErrorDomain)errorDomain;
// @end

// TODO: cblocks in fields are not working but are they in frameworks?
// typedef struct MyStruct {
//     double (^)(double, double) block;
// } MyStruct;

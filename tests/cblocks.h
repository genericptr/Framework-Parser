

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

  completionHandler:(void (^ _Nullable)(NSDictionary<NSURL *, NSURL *> * newURLs, NSError * _Nullable error))
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
@end

// TODO: cblocks in fields are not working but are they in frameworks?
typedef struct MyStruct {
    double (^)(double, double) block;
} MyStruct;

// TODO: cblocks in properties (can these have names?)
// @interface NSExpression : NSObject
// @property(copy) double (^name)(double, double) *minificationFilter;
// @end

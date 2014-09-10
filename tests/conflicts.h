

// type conflict - #defines are made constants but this is an external function
// the type must be removed, but make a notice
// we could also make comment symbols now and manually insert them
// for these and inline functions for example
#define NSImageRepRegistryChangedNotification NSImageRepRegistryDidChangeNotification /* obsolete name */
APPKIT_EXTERN NSString *NSImageRepRegistryDidChangeNotification;


// order conflict
// TopType must be declared below BottomType
typedef BottomType TopType;
#define BottomType 100;


/*

@interface NSObject(NSExtras)
- (BOOL)foo;
// needs protection I think
- (void)copy:(id)sender;
@end

// class protection should prefix classLoad
@interface NSObject <NSObject>
+ (void)load;
- (void)copy;
@end

@interface NSPersistentStore : NSObject
- (BOOL)foo;
@end

@interface NSAtomicStore : NSPersistentStore
- (BOOL)load:(NSError **)error;
- (BOOL)foo;
//- (BOOL)foo;
//- (BOOL)foo:(int) param;
- (void)copy:(id) sender;
@end

// these properties need to not be protected!
@interface UIScrollView : UIView
@property(nonatomic,assign) id<UIScrollViewDelegate> delegate; 
@end

@interface UITextView : UIScrollView
@property(nonatomic,assign) id<UITextViewDelegate> delegate;
@property(nonatomic,assign) id<UITextViewDelegate> delegate;
@end

*/

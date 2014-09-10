
// http://developer.apple.com/library/mac/#documentation/Cocoa/Conceptual/ObjectiveC/Chapters/ocProtocols.html#//apple_ref/doc/uid/TP30001163-CH15-SW1

// enums/types in protocols
@protocol NSFetchedResultsControllerDelegate
enum {
	NSFetchedResultsChangeInsert = 1,
	NSFetchedResultsChangeDelete = 2,
	NSFetchedResultsChangeMove = 3,
	NSFetchedResultsChangeUpdate = 4
	
};
typedef NSUInteger NSFetchedResultsChangeType;
@end

// foward declaration;
@protocol NSForwardProtocol, UIAppearanceContainer;

// single line protocol
@protocol UIAppearanceContainer <NSObject> @end

@protocol NSOther
- (void)defaultItem:(id)anItem;

// this method name need to be protected for the protocol "NSOtherProtocol"
- (BOOL)conformsToProtocol:(Protocol *)aProtocol;
@end

@protocol NSMain <NSOther>
@optional
- (BOOL)optionalItemA:(id <NSOther>)anItem;
- (BOOL)optionalItemA:(id <NSOther>)anItem;
@required
- (BOOL)requiredItem:(id <NSOther>)anItem;
@end


@interface NSSomeClass : NSObject <NSMain>  {
	id <NSOther> someClass;
}
+ (id)sharedClass;
@end

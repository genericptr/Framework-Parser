
/*

we still have the BEGIN_DECALS problem
see SMLoginItem

*/

CORE_IMAGE_CLASS_EXPORT
@interface CIVector : NSObject <NSCopying, NSCoding>
{
    size_t _count;

    union {
        CGFloat vec[4];
        CGFloat *ptr;
    }
		_u;
}

/* Create a new vector object. */

+ (CIVector *)vectorWithValues:(const CGFloat *)values count:(size_t)count;

+ (CIVector *)vectorWithX:(CGFloat)x;
+ (CIVector *)vectorWithX:(CGFloat)x Y:(CGFloat)y;
+ (CIVector *)vectorWithX:(CGFloat)x Y:(CGFloat)y Z:(CGFloat)z;
+ (CIVector *)vectorWithX:(CGFloat)x Y:(CGFloat)y Z:(CGFloat)z W:(CGFloat)w;

+ (CIVector *)vectorWithString:(NSString *)representation;

/* Initializers. */

- (id)initWithValues:(const CGFloat *)values count:(size_t)count;

- (id)initWithX:(CGFloat)x;
- (id)initWithX:(CGFloat)x Y:(CGFloat)y;
- (id)initWithX:(CGFloat)x Y:(CGFloat)y Z:(CGFloat)z;
- (id)initWithX:(CGFloat)x Y:(CGFloat)y Z:(CGFloat)z W:(CGFloat)w;

- (id)initWithString:(NSString *)representation;

/* Return the value from the vector at position 'index' (zero-based).
 * Any 'index' value is valid, if the component would otherwise be
 * undefined, zero is returned. */
- (CGFloat)valueAtIndex:(size_t)index;

/* Return the number of values stored in the vector. */
- (size_t)count;

/* Getters. */

- (CGFloat)X;
- (CGFloat)Y;
- (CGFloat)Z;
- (CGFloat)W;

/* Return a string representing the vector such that a similar vector
 * can be created by calling the vectorWithString: method. */
- (NSString *)stringRepresentation;

@end

DIE_NOW

// bad macro
#if TARGET_OS_IPHONE
NS_CLASS_AVAILABLE(NA, 4_1)
@interface GKMatchmakerViewController : UINavigationController
@end
#else
#import <GameKit/GKDialogController.h>
NS_CLASS_AVAILABLE(10_8, NA)
@interface GKMatchmakerViewController : NSViewController <GKViewController> {
	id _internal1,_internal2,_internal3,_internal4;
}
@end
#endif

@interface GKMatchmakerViewController ()

@property(assign, NS_NONATOMIC_IOSONLY) id<GKMatchmakerViewControllerDelegate>     matchmakerDelegate;
@property(readonly, retain, NS_NONATOMIC_IOSONLY) GKMatchRequest                   *matchRequest;
@property(assign, getter=isHosted, NS_NONATOMIC_IOSONLY) BOOL                      hosted;  // set to YES to receive hosted (eg. not peer-to-peer) match results. Will cause the controller to return an array of players instead of a match.
@property(copy, NS_NONATOMIC_IOSONLY) NSString                                     *defaultInvitationMessage __OSX_AVAILABLE_STARTING(__MAC_10_8,__IPHONE_5_0); // default message to use when inviting friends. Can be edited by the user.

// Initialize with a matchmaking request, allowing the user to send invites and/or start matchmaking
- (id)initWithMatchRequest:(GKMatchRequest *)request;

// Initialize with an accepted invite, allowing the user to see the status of other invited players and get notified when the game starts
- (id)initWithInvite:(GKInvite *)invite;

// Add additional players (not currently connected) to an existing peer-to-peer match.  
// Apps should elect a single device to do this, otherwise conflicts could arise resulting in unexpected connection errors.
- (void)addPlayersToMatch:(GKMatch *)match __OSX_AVAILABLE_STARTING(__MAC_10_8,__IPHONE_5_0);

// Update the displayed connection status for a remote server-hosted player
- (void)setHostedPlayer:(NSString *)playerID connected:(BOOL)connected __OSX_AVAILABLE_STARTING(__MAC_10_8,__IPHONE_5_0);

// Deprecated, use setConnected:forHostedPlayer: instead.
- (void)setHostedPlayerReady:(NSString *)playerID __OSX_AVAILABLE_BUT_DEPRECATED(__MAC_NA,__MAC_NA,__IPHONE_4_1,__IPHONE_5_0);

@end

DIE_NOW

@interface AVPlayerItemVideoOutput : AVPlayerItemOutput
@property (nonatomic, readonly) id<AVPlayerItemOutputPullDelegate>delegate;
@end

DIE_NOW

// enums with colons?
typedef enum : NSInteger
{
	SKDownloadStateWaiting,
	SKDownloadStateActive,
	SKDownloadStatePaused,
	SKDownloadStateFinished,
	SKDownloadStateFailed,
	SKDownloadStateCancelled
}
SKDownloadState;

DIE_NOW

// inline blocks
@interface NSCustomImageRep : NSImageRep
- (id)initWithSize:(NSSize)size flipped:(BOOL)drawingHandlerShouldBeCalledWithFlippedContext drawingHandler:(BOOL (^)(NSRect dstRect))drawingHandler NS_AVAILABLE_MAC(10_8);
- (BOOL (^)(NSRect dstRect))drawingHandler NS_AVAILABLE_MAC(10_8);
@end

DIE_NOW

// blocks in ivar sections
@interface NSXPCConnection : NSObject <NSXPCProxyCreating> {
@private
    void (^_interruptionHandler)();
    void (^_invalidationHandler)();
}
@end

// new enum macros in 10.8
typedef NS_ENUM(NSUInteger, NSSearchPathDirectory) {
    NSApplicationDirectory = 1,             // supported applications (Applications)
    NSDemoApplicationDirectory,             // unsupported applications, demonstration versions (Demos)
    NSDeveloperApplicationDirectory,        // developer applications (Developer/Applications). DEPRECATED - there is no one single Developer directory.
    NSAdminApplicationDirectory,            // system and network administration applications (Administration)
    NSLibraryDirectory,                     // various documentation, support, and configuration files, resources (Library)
    NSDeveloperDirectory,                   // developer resources (Developer) DEPRECATED - there is no one single Developer directory.
    NSUserDirectory,                        // user home directories (Users)
    NSDocumentationDirectory,               // documentation (Documentation)
    NSDocumentDirectory,                    // documents (Documents)
    NSCoreServiceDirectory,                 // location of CoreServices directory (System/Library/CoreServices)
    NSAutosavedInformationDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 11,   // location of autosaved documents (Documents/Autosaved)
    NSDesktopDirectory = 12,                // location of user's desktop
    NSCachesDirectory = 13,                 // location of discardable cache files (Library/Caches)
    NSApplicationSupportDirectory = 14,     // location of application support files (plug-ins, etc) (Library/Application Support)
    NSDownloadsDirectory NS_ENUM_AVAILABLE(10_5, 2_0) = 15,              // location of the user's "Downloads" directory
    NSInputMethodsDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 16,           // input methods (Library/Input Methods)
    NSMoviesDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 17,                 // location of user's Movies directory (~/Movies)
    NSMusicDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 18,                  // location of user's Music directory (~/Music)
    NSPicturesDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 19,               // location of user's Pictures directory (~/Pictures)
    NSPrinterDescriptionDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 20,     // location of system's PPDs directory (Library/Printers/PPDs)
    NSSharedPublicDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 21,           // location of user's Public sharing directory (~/Public)
    NSPreferencePanesDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 22,        // location of the PreferencePanes directory for use with System Preferences (Library/PreferencePanes)
    NSApplicationScriptsDirectory NS_ENUM_AVAILABLE(10_8, NA) = 23,      // location of the user scripts folder for the calling application (~/Library/Application Scripts/code-signing-id)
    NSItemReplacementDirectory NS_ENUM_AVAILABLE(10_6, 4_0) = 99,	    // For use with NSFileManager's URLForDirectory:inDomain:appropriateForURL:create:error:
    NSAllApplicationsDirectory = 100,       // all directories where applications can occur
    NSAllLibrariesDirectory = 101,          // all directories where resources can occur
    NSTrashDirectory NS_ENUM_AVAILABLE(10_8, NA) = 102                   // location of Trash directory
};

typedef NS_OPTIONS(unsigned long long, NSAlignmentOptions) {
    NSAlignMinXInward   = 1ULL << 0,
    NSAlignMinYInward   = 1ULL << 1,
    NSAlignMaxXInward   = 1ULL << 2,
    NSAlignMaxYInward   = 1ULL << 3,
    NSAlignWidthInward  = 1ULL << 4,
    NSAlignHeightInward = 1ULL << 5,
    
    NSAlignMinXOutward   = 1ULL << 8,
    NSAlignMinYOutward   = 1ULL << 9,
    NSAlignMaxXOutward   = 1ULL << 10,
    NSAlignMaxYOutward   = 1ULL << 11,
    NSAlignWidthOutward  = 1ULL << 12,
    NSAlignHeightOutward = 1ULL << 13,
    
    NSAlignMinXNearest   = 1ULL << 16,
    NSAlignMinYNearest   = 1ULL << 17,
    NSAlignMaxXNearest   = 1ULL << 18,
    NSAlignMaxYNearest   = 1ULL << 19,
    NSAlignWidthNearest  = 1ULL << 20,
    NSAlignHeightNearest = 1ULL << 21,
    
    NSAlignRectFlipped = 1ULL << 63, // pass this if the rect is in a flipped coordinate system. This allows 0.5 to be treated in a visually consistent way.

    // convenience combinations
    NSAlignAllEdgesInward = NSAlignMinXInward|NSAlignMaxXInward|NSAlignMinYInward|NSAlignMaxYInward,
    NSAlignAllEdgesOutward = NSAlignMinXOutward|NSAlignMaxXOutward|NSAlignMinYOutward|NSAlignMaxYOutward,
    NSAlignAllEdgesNearest = NSAlignMinXNearest|NSAlignMaxXNearest|NSAlignMinYNearest|NSAlignMaxYNearest,
};

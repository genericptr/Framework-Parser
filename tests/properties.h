
// http://developer.apple.com/library/mac/#documentation/Cocoa/Conceptual/ObjectiveC/Chapters/ocProperties.html#//apple_ref/doc/uid/TP30001163-CH17-SW1

// property attributes
/*
	assign
	retain
	copy
	nonatomic
	atomic
	readwrite
	readonly
*/

@interface NSProperties

// property with multiple names
@property(retain) id fromValue, toValue, byValue;
@property(copy) NSString *minificationFilter, *magnificationFilter;

// property with without attributes and protocol hint
@property id <NSObject> objWithHint;
@property(readonly, retain) id<NSObject, NSCopying> identity;

// blocks in properties
@property (copy) void (^terminationHandler)(NSTask *) NS_AVAILABLE(10_7, NA);

// function pointers in property with nested function pointer parameters
@property NSUInteger (*hashFunction)(const void *item, NSUInteger (*size)(const void *item));
@property BOOL (*isEqualFunction)(const void *item1, const void *item2, NSUInteger (*size)(const void *item));
@property NSUInteger (*sizeFunction)(const void *item);
@property NSString *(*descriptionFunction)(const void *item);
@property usigned long long (*descriptionFunction)(const void *item);

// property with getter specified
@property(nonatomic,getter=isBoolean) 	BOOL someBoolean;

// property with setter specified
@property(nonatomic,setter=putBoolean) 	BOOL otherBoolean;

// property with readonly propery (only getter, no setter)
@property(nonatomic,readonly,retain)    CALayer  *layer;              

// property with without attributes (specifies read/write methods)
@property float floatValue;

// property with triple word type
@property unsigned long long tripleWordValue;

// In Mac OS X v10.6 and later, you can use the __attribute__ keyword to specify that a Core Foundation property 
// should be treated like an Objective-C object for memory management:
@property(retain) __attribute__((NSObject)) CFDictionaryRef myDictionary;

// availablity macro that must be removed
@property(nonatomic) CGFloat floatValue __OSX_AVAILABLE_STARTING(__MAC_NA,__IPHONE_4_0);

// If you want to specify that a property is an Interface Builder outlet, you can use the IBOutlet identifier:
@property (nonatomic, retain) IBOutlet NSButton *myButton;

// If you use garbage collection, you can use the storage modifiers __weak and __strong in a property’s declaration:
@property (nonatomic, retain) __weak int myValue;

@end

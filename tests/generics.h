

// TODO: typed generic param
@interface NSMeasurement<UnitType: NSUnit *, KeyType, ObjectType> : NSObject<NSCopying, NSSecureCoding> {
@private
    UnitType _unit;
    double _doubleValue;
}
@property (readonly, copy) UnitType unit;
- (instancetype)initWithDoubleValue:(double)doubleValue unit:(UnitType)unit NS_DESIGNATED_INITIALIZER;
@end



// todo: do protocols handle generics?
@protocol NSFetchedResultsControllerDelegate<KeyType, ObjectType>
- (ObjectType)defaultItem:(KeyType)anItem;
@end

// todo: broken c-block caused by generic NSOrderedCollectionChange<ObjectType> 
/*
  function differenceByTransformingChangesWithBlock (block: NSOrderedCollectionChange *)): NSOrderedCollectionDifference; message 'differenceByTransformingChangesWithBlock:';
*/
// @interface NSOrderedCollectionDifference<ObjectType> : NSObject <NSFastEnumeration>
// - (NSOrderedCollectionDifference<id> *)differenceByTransformingChangesWithBlock:(NSOrderedCollectionChange<id> *(NS_NOESCAPE ^)(NSOrderedCollectionChange<ObjectType> *))block;
// @end

/*
@interface NSMutableDictionary<KeyType, ObjectType> : NSDictionary<KeyType, ObjectType>
@end

@interface NSDictionary<KeyType, ObjectType> (NSExtendedDictionary)
@property (readonly, copy) NSArray<KeyType> *allKeys;
@property (readonly, copy) NSArray<ObjectType> *allValues;
@property (readonly, copy) ObjectType description;
@property (copy) KeyType descriptionInStringsFileFormat;
- (NSArray<KeyType> *)allKeysForObject:(ObjectType)anObject;
@end

@interface NSArray<ObjectType> (NSExtendedArray)
- (NSArray<ObjectType> *)sortedArrayUsingFunction:(NSInteger (NS_NOESCAPE *)(ObjectType, ObjectType, void * _Nullable))comparator context:(nullable void *)context;
@end

@interface NSDictionary<__covariant KeyType, __covariant ObjectType> : NSObject <NSCopying, NSMutableCopying, NSSecureCoding, NSFastEnumeration>

@property (readonly) NSUInteger count;
- (nullable ObjectType)objectForKey:(KeyType)aKey;
- (NSEnumerator<KeyType> *)keyEnumerator;
- (instancetype)init NS_DESIGNATED_INITIALIZER;
- (instancetype)initWithObjects:(const ObjectType _Nonnull [_Nullable])objects forKeys:(const KeyType <NSCopying> _Nonnull [_Nullable])keys count:(NSUInteger)cnt NS_DESIGNATED_INITIALIZER;
- (nullable instancetype)initWithCoder:(NSCoder *)coder NS_DESIGNATED_INITIALIZER;

@end


@interface NSArray<ObjectType> (NSExtendedArray)

- (NSArray<ObjectType> *)hasItemConformingToTypeIdentifier:(NSString *)typeIdentifier;
- (NSArray<ObjectType> *)sortedArrayUsingFunction:(NSInteger (NS_NOESCAPE *)(ObjectType, ObjectType, void * _Nullable))comparator context:(nullable void *)context;
- (NSArray<ObjectType> *)sortedArrayUsingFunction:(NSInteger (NS_NOESCAPE *)(ObjectType, ObjectType, void * _Nullable))comparator context:(nullable void *)context hint:(nullable NSData *)hint;

@end

// TODO: how do we know if this is a protocol or  generic param?
@interface NSXPCConnection<SomeObject> {
@private
    void (^_interruptionHandler)();
    void (^_invalidationHandler)();
}
@end


@interface NSMutableArray<ObjectType, KeyType> : NSArray<ObjectType>
- (KeyType)addObject:(ObjectType)anObject;
- (void)removeObjectByKey:(KeyType)aKey;
@end


@interface NSMutableArray<ObjectType> : NSArray<ObjectType>
- (void)addObject:(ObjectType)anObject;
@end

@interface NSDictionary<KeyType, ObjectType>: NSObject <NSCopying, NSMutableCopying, NSSecureCoding, NSFastEnumeration>
wrap:(BOOL)wrapFlag 
@end

*/

@interface NSObject
- (void) rootMethod;
@end

// already declared named of a class, must protect
@interface CALayer (CAConstraintLayoutManager)
- (void)addConstraint:(CAConstraint *)c;
@end

@interface CAConstraintLayoutManager : NSObject
+ (id)layoutManager;
@end

// 1) rootMethod was declared in NSObject
@interface NSObject(MyCategory)
- (void) rootMethod;
- (void) categoryMethodA;
- (void) categoryMethodB: (int)rootMethod with:(NSObject)MyCategory;
@end

// 1) the name MyCategory already exists in global namespace (make unique for base class NSArray_MyCategory)
// 2) rootMethod was declared in MyCategory and NSObject
// 3) categoryMethod was declared in MyCategory
@interface NSObject(MyCategory)
- (void) rootMethod;
- (void) categoryMethodA;
- (void) categoryMethodB: (int)rootMethod with:(NSObject)MyCategory;
@end
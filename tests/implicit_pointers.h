
typedef int NSInteger;
typedef void* NSRect;
typedef unsigned long long type1, type2;

@class NSString, NSData;

@interface MyClass {
	MyClass *field;	// MyClass is a class and therefore implicit
}

// These class pointers are implicit because they were declared as forwards using @class
- (void)setColorSpaceName:(NSString *)string;
+ (BOOL)canInitWithData:(NSData *)data;

// NSRect is a pointer and NSRectPtr has been declared
// ** declares even implicit pointers as pointers to themselves
- (void)doSomething1:(const NSRect **)value;

// int has known pointer of pint and the 2nd * will
// be ignored because pint is already a pointer
- (void)doSomething2:(int **)value;

@end

// function pointers are implicit pointers so don't suffix their types
typedef void NSUncaughtExceptionHandler(NSException *exception);

// pointers to NSUncaughtExceptionHandler will not be suffixed
FOUNDATION_EXPORT NSUncaughtExceptionHandler *NSGetUncaughtExceptionHandler(void);
FOUNDATION_EXPORT void NSSetUncaughtExceptionHandler(NSUncaughtExceptionHandler *);

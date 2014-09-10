
#if A >= A
@interface MyClassC : MyClassB {
	
	union {
      struct {
          int eventNumber;
          int clickCount;
          float pressure;
      } mouse;

      struct {
          int eventNumber;
          int clickCount; DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER
          float pressure;
      } mouse_2;
  } _data;
	
	__strong void **void1;
	__strong void *void2;
	void*          void3;
	NSObject*      void4;
	
	// in symbol table call add_used_type as a struct to the type is opaque
	struct PATHSEGMENT  _prvStruct1; DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER
	struct PATHSEGMENT* _prvStruct2; DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER
	
	unsigned int _privateArray[10];
	
	#if !__LP64__
	long long _private3;
	#endif
	
	/*
	__strong unsigned long _field;
	__weak unsigned int _private1;
	unsigned int _privateArray[10];
	unsigned int *_privateArrayPtr[10];
	unsigned int *_privatePtr;
	struct _prvStruct	_privateStruct;		
	id <MyProtocol> _privateSomething;
	
	#if !__LP64__
	long long _private3;
	#endif
	*/
	
	#if !__LP64__
	struct {
		int _field1:1;
		int _field2:5;
		int _field3:8;
	} __flags;
	#endif
	
}

// ??? if we add this the field parser starts taking all the methods???
#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
- (void) doThis1: (int)_currentEvent with:(int)_appFlags; DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER
- (void) doThis2: (int)_currentEvent with:(int)_appFlags;
#endif

- (void) doThis3: (int)_currentEvent with:(int)_appFlags;
- (void) doThis4: (int)_currentEvent with:(int)_appFlags;

#if NS_BLOCKS_AVAILABLE
+ (id)addGlobalMonitorForEventsMatchingMask:(NSEventMask)mask handler:(void (^)(NSEvent*))block AVAILABLE_MAC_OS_X_VERSION_10_6_AND_LATER;
+ (id)addLocalMonitorForEventsMatchingMask:(NSEventMask)mask handler:(NSEvent* (^)(NSEvent*))block AVAILABLE_MAC_OS_X_VERSION_10_6_AND_LATER;
+ (void)removeMonitor:(id)eventMonitor AVAILABLE_MAC_OS_X_VERSION_10_6_AND_LATER;
#endif

@end
#endif

enum {        /* event subtypes for NSAppKitDefined events */
    NSWindowExposedEventType            = 0,
    NSApplicationActivatedEventType     = 1,
    NSApplicationDeactivatedEventType   = 2,
    NSWindowMovedEventType              = 4,
    NSScreenChangedEventType            = 8,
    NSAWTEventType                      = 16
};

@interface MyClassD : MyClassC
	- (void) doThis1: (int)_currentEvent with:(int)_appFlags; DEPRECATED_IN_MAC_OS_X_VERSION_10_5_AND_LATER
	- (void) doThis2: (int)_currentEvent with:(int)_appFlags;
@end

#if D >= D
struct myStruct {
	int field;
	int private;
};
#endif

/*
@interface NSWindow : NSResponder

#if MAC_OS_X_VERSION_MAX_ALLOWED >= MAC_OS_X_VERSION_10_5
<NSAnimatablePropertyContainer, NSUserInterfaceValidations>
#else
<NSUserInterfaceValidations>
#endif

@end
*/
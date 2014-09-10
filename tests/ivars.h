

// block in ivar
@interface UIViewController : NSObject {
@private
	void (^_afterAppearance)(void);
}
@end

@interface MyClassB : NSObject {
	  @private
	    NSEvent            *_currentEvent;
	    id     							_windowList;
	    id                  _keyWindow;
	    id                  _mainWindow;
			__strong void 			*_cacheA;					// garbage collector hint 
			__weak void 				*_cacheB;					// garbage collector hint
			UIAccelerationValue x, y, z;					// lists that need to protect names
	    
		@private
			id 									_private[2];								// inline array
			
													// inline array pointer
													// if the type is not defined just add a plain pointer which has the same size anyways
			id 									*_privateArrayPointer[2];		
			
													// protocol that we adopt hints
			id<protocol> 			_name;
			
			// undeclared private struct
			// if we parsed all types up to this point check if the struct exists (be remove the struct keyword)
			// and if it doesn't assume it's private and create an empty struct
			struct _prvStruct		_privateA;									
			
			// bit alignment fields outside of struct
			unsigned char authGen:1;
	    unsigned char authCheck:1;
	    unsigned char encryptFlag:1;
			
		// availability blocks - important in ivars!
		#if !__LP64__
		    id _fpUnused[72];
		#endif
			
			// unions
			// I think we need to just add an empty struct as a record for
			// byte alignment but better ask jonas
		  union {
	        struct {
	            int eventNumber;
	            int clickCount;
	            float pressure;
	        } mouse;
	
	        struct {
	            int eventNumber;
	            int clickCount;
	            float pressure;
	        } mouse_2;
	    } _data;
			
		// structs
		@protected
	    struct __appFlags {
				unsigned int        _reservedA:1;
				unsigned int        _reservedB:2;
				unsigned int        _reservedC:3 __attribute__((deprecated));	// should insert deprecated keyword
	    }                   _appFlags;
		@public
	    id                  _mainMenu DEPRECATED_IN_MAC_OS_X_VERSION_10_4_AND_LATER; // macros can appear anywhere!
	    id                  _appIcon;
	    id                  _nameTable;
	    id                  _eventDelegate;
	    _NSThreadPrivate     *_threadingSupport;
}

}

@property(nonatomic,readonly) NSTimeInterval timestamp;
@property(nonatomic,readonly) UIAccelerationValue x;
@property(nonatomic,readonly) UIAccelerationValue y;
@property(nonatomic,readonly) UIAccelerationValue z;

@end
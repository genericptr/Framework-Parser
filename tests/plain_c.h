

// no return type with macros
int CPL_DLL CPL_STDCALL GDALGetDataTypeSize( GDALDataType );

//  broken nested callbacks
extern int CPhidget_set_OnDetach_Handler(CPhidgetHandle phid, int( *fptr)(CPhidgetHandle phid, void *userPtr), void *userPtr);

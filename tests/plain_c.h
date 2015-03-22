

// no return type
int GDALGetDataTypeSize(GDALDataType);

//  broken nested callbacks
extern int CPhidget_set_OnDetach_Handler(CPhidgetHandle phid, int( *fptr)(CPhidgetHandle phid, void *userPtr), void *userPtr);

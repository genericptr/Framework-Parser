
{$ifndef FRAMEWORK_LOADED_COREIMAGE}
type
	CIColor = NSObject;
	CIColorPtr = ^CIColor;
	
	CIImage = NSObject;
	CIImagePtr = ^CIImage;
{$endif}
	
{ MacOSPosix.pas }
type
	dispatch_queue_t = pointer;
  dispatch_semaphore_t = pointer;
	pthread_mutex_t = pointer;
	
// from UICollectionViewLayout.h
type
	instancetype = pointer;
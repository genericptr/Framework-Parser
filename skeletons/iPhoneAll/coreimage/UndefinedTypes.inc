{$ifndef FRAMEWORK_LOADED_COREVIDEO}
type
	CVImageBufferRef = pointer;
	CVPixelBufferRef = pointer;
{$endif}

{$ifndef FRAMEWORK_LOADED_OPENGL}
type
	CGLContextObj = pointer;
	CGLPixelFormatObj = pointer;
{$endif}

{$if not defined(TARGET_OS_IPHONE)}
type
	IOSurfaceRef = OpaqueType;
{$endif}

type
	pthread_mutex_t = pointer;
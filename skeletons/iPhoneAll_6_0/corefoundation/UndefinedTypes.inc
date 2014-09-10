
// acl.h
type
	acl_t = OpaqueType;
	acl_tPtr = ^acl_t;
	
	uid_t = OpaqueType;
	uid_tPtr = ^uid_t;
	
	gid_t = OpaqueType;
	gid_tPtr = ^gid_t;
	
	mode_t = OpaqueType;
	mode_tPtr = ^mode_t;
	
type
	FSRef = OpaqueRecord;
	FSRefPtr = ^FSRef;
	
// MacTypes
type
  UTF32Char = UInt32;
  UTF32CharPtr = ^UTF32Char;
  UTF16Char = UInt16;
  UTF16CharPtr = ^UTF16Char;
  UTF8Char = UInt8;
  UTF8CharPtr = ^UTF8Char;
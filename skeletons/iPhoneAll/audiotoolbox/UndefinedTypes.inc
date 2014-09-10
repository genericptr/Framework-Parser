type
	AudioObjectID = OpaqueType;
	AudioObjectPropertyAddress = OpaqueType;
	AudioObjectPropertyListenerProc = OpaqueType;
	
type
	ComponentDescription = OpaqueRecord;
	ComponentDescriptionPtr = ^ComponentDescription;
	
type
	FSRef = OpaqueRecord;
	FSRefPtr = ^FSRef;
	
{$ifndef TARGET_OS_IPHONE}
type
  CABarBeatTime = record
    bar: SInt32;
    beat: UInt16;
    subbeat: UInt16;
    subbeatDivisor: UInt16;
    reserved: UInt16;
  end;
  CABarBeatTimePtr = ^CABarBeatTime;
{$endif}

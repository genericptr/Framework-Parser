type
	CGPDFDictionaryRefPtr = OpaqueType;

// CGBase.h is ignored so define it here using
// types from MacTypes.pas
{$if defined(TARGET_CPU_64)}
type
  CGFloat = Float64;

const
  CGFloat_Min = Float64_Min;
  CGFloat_Max = Float64_Max;
{$else}
type
  CGFloat = Float32;

const
  CGFloat_Min = Float32_Min;
  CGFloat_Max = Float32_Max;
{$endif}

type
  CGFloatPtr = ^CGFloat;

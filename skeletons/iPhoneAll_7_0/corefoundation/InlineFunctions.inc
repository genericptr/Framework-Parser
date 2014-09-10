
{$ifdef INTERFACE}

// CFString.h
function CFSTR (c: string): CFStringRef; overload;
function CFSTR (c: pchar): CFStringRef; overload;
function CFSTRP (c: pchar): CFStringRef; overload;

// CFBase.h
function CFRangeMake( loc: CFIndex; len: CFIndex ): CFRange;

// CFBundle.h
function CFCopyLocalizedString( key: CFStringRef; comment: PChar ): CFStringRef;
function CFCopyLocalizedStringFromTable( key: CFStringRef; tableName: CFStringRef; comment: PChar ): CFStringRef;
function CFCopyLocalizedStringFromTableInBundle( key: CFStringRef; tableName: CFStringRef; bundle: CFBundleRef; comment: PChar ): CFStringRef;
function CFCopyLocalizedStringWithDefaultValue( key: CFStringRef; tableName: CFStringRef; bundle: CFBundleRef; value: CFStringRef; comment: PChar ): CFStringRef;

// CFByteOrder.h
function CFByteOrderGetCurrent: CFByteOrder;
function CFSwapInt16( arg: UInt16 ): UInt16;
function CFSwapInt32( arg: UInt32 ): UInt32;
function CFSwapInt64( arg: UInt64 ): UInt64;

function CFSwapInt16BigToHost( arg: UInt16 ): UInt16;
function CFSwapInt32BigToHost( arg: UInt32 ): UInt32;
function CFSwapInt64BigToHost( arg: UInt64 ): UInt64;
function CFSwapInt16HostToBig( arg: UInt16 ): UInt16;
function CFSwapInt32HostToBig( arg: UInt32 ): UInt32;
function CFSwapInt64HostToBig( arg: UInt64 ): UInt64;

function CFConvertFloat32HostToSwapped( arg: Float32 ): CFSwappedFloat32;
function CFConvertFloat32SwappedToHost( arg: CFSwappedFloat32 ): Float32;
function CFConvertFloat64HostToSwapped( arg: Float64 ): CFSwappedFloat64;
function CFConvertFloat64SwappedToHost( arg: CFSwappedFloat64 ): Float64;
function CFConvertFloatHostToSwapped( arg: Float32 ): CFSwappedFloat32;
function CFConvertFloatSwappedToHost( arg: CFSwappedFloat32 ): Float32;
function CFConvertDoubleHostToSwapped( arg: Float64 ): CFSwappedFloat64;
function CFConvertDoubleSwappedToHost( arg: CFSwappedFloat64 ): Float64;

{$endif}

{$ifdef IMPLEMENTATION}
function CFSTR (c: string): CFStringRef; overload;
begin
	c := c+''+chr(0);
	result := CFSTRP(@c[1]);
end;

function CFSTR (c: pchar): CFStringRef; overload;
begin
	result := CFSTRP(c);
end;

function CFSTRP (c: pchar): CFStringRef; overload;
begin
	result := __CFStringMakeConstantString(c);
end;

function CFRangeMake( loc: CFIndex; len: CFIndex ): CFRange;
begin
	result.location := loc;
	result.length := len;
end;

function CFCopyLocalizedString( key: CFStringRef; comment: PChar ): CFStringRef;
begin
	CFCopyLocalizedString := CFBundleCopyLocalizedString( CFBundleGetMainBundle, key, key, nil );
end;

function CFCopyLocalizedStringFromTable( key: CFStringRef; tableName: CFStringRef; comment: PChar ): CFStringRef;
begin
	CFCopyLocalizedStringFromTable := CFBundleCopyLocalizedString( CFBundleGetMainBundle, key, key, tableName );
end;

function CFCopyLocalizedStringFromTableInBundle( key: CFStringRef; tableName: CFStringRef; bundle: CFBundleRef; comment: PChar ): CFStringRef;
begin
	CFCopyLocalizedStringFromTableInBundle := CFBundleCopyLocalizedString( bundle, key, key, tableName );
end;

function CFCopyLocalizedStringWithDefaultValue( key: CFStringRef; tableName: CFStringRef; bundle: CFBundleRef; value: CFStringRef; comment: PChar ): CFStringRef;
begin
	CFCopyLocalizedStringWithDefaultValue := CFBundleCopyLocalizedString( bundle, key, value, tableName );
end;

function CFByteOrderGetCurrent: CFByteOrder;
var
  x: UInt32 = (CFByteOrderBigEndian shl 24) or CFByteOrderLittleEndian;
begin
  CFByteOrderGetCurrent := CFByteOrder(UInt8Ptr(@x)^);
end;

function CFSwapInt16( arg: UInt16 ): UInt16;
begin
  CFSwapInt16 := (( arg shl 8) and $0FF00) or (( arg shr 8) and $00FF);
end;

function CFSwapInt32( arg: UInt32 ): UInt32;
begin
  CFSwapInt32 := ((arg and $FF) shl 24) or ((arg and $0FF00) shl 8) or ((arg shr 8) and $0FF00) or ((arg shr 24) and $FF);
end;

function CFSwapInt64( arg: UInt64 ): UInt64;
begin
  CFSwapInt64 := (UInt64(CFSwapInt32( arg and $FFFFFFFF )) shl 32) or CFSwapInt32( (arg shr 32) and $FFFFFFFF );
end;

{$if defined(TARGET_RT_BIG_ENDIAN)}
function CFSwapInt16BigToHost( arg: UInt16 ): UInt16;
begin
  CFSwapInt16BigToHost := arg;
end;

function CFSwapInt32BigToHost( arg: UInt32 ): UInt32;
begin
  CFSwapInt32BigToHost := arg;
end;

function CFSwapInt64BigToHost( arg: UInt64 ): UInt64;
begin
  CFSwapInt64BigToHost := arg;
end;

function CFSwapInt16HostToBig( arg: UInt16 ): UInt16;
begin
  CFSwapInt16HostToBig := arg;
end;

function CFSwapInt32HostToBig( arg: UInt32 ): UInt32;
begin
  CFSwapInt32HostToBig := arg;
end;

function CFSwapInt64HostToBig( arg: UInt64 ): UInt64;
begin
  CFSwapInt64HostToBig := arg;
end;

function CFSwapInt16LittleToHost( arg: UInt16 ): UInt16;
begin
  CFSwapInt16LittleToHost := CFSwapInt16(arg);
end;

function CFSwapInt32LittleToHost( arg: UInt32 ): UInt32;
begin
  CFSwapInt32LittleToHost := CFSwapInt32(arg);
end;

function CFSwapInt64LittleToHost( arg: UInt64 ): UInt64;
begin
  CFSwapInt64LittleToHost := CFSwapInt64(arg);
end;

function CFSwapInt16HostToLittle( arg: UInt16 ): UInt16;
begin
  CFSwapInt16HostToLittle := CFSwapInt16(arg);
end;

function CFSwapInt32HostToLittle( arg: UInt32 ): UInt32;
begin
  CFSwapInt32HostToLittle := CFSwapInt32(arg);
end;

function CFSwapInt64HostToLittle( arg: UInt64 ): UInt64;
begin
  CFSwapInt64HostToLittle := CFSwapInt64(arg);
end;

function CFConvertFloat32HostToSwapped( arg: Float32 ): CFSwappedFloat32;
begin
  CFConvertFloat32HostToSwapped := CFSwappedFloat32(arg);
end;

function CFConvertFloat32SwappedToHost( arg: CFSwappedFloat32 ): Float32;
begin
  CFConvertFloat32SwappedToHost := Float32(arg);
end;

function CFConvertFloat64HostToSwapped( arg: Float64 ): CFSwappedFloat64;
begin
  CFConvertFloat64HostToSwapped := CFSwappedFloat64(arg);
end;

function CFConvertFloat64SwappedToHost( arg: CFSwappedFloat64 ): Float64;
begin
  CFConvertFloat64SwappedToHost := Float64(arg);
end;

function CFConvertFloatHostToSwapped( arg: Float32 ): CFSwappedFloat32;
begin
  CFConvertFloatHostToSwapped := CFSwappedFloat32(arg);
end;

function CFConvertFloatSwappedToHost( arg: CFSwappedFloat32 ): Float32;
begin
  CFConvertFloatSwappedToHost := Float32(arg);
end;

function CFConvertDoubleHostToSwapped( arg: Float64): CFSwappedFloat64;
begin
  CFConvertDoubleHostToSwapped := CFSwappedFloat64(arg);
end;

function CFConvertDoubleSwappedToHost( arg: CFSwappedFloat64 ): Float64;
begin
  CFConvertDoubleSwappedToHost := Float64(arg);
end;

{$else}

function CFSwapInt16LittleToHost( arg: UInt16 ): UInt16;
begin
  CFSwapInt16LittleToHost := arg;
end;

function CFSwapInt32LittleToHost( arg: UInt32 ): UInt32;
begin
  CFSwapInt32LittleToHost := arg;
end;

function CFSwapInt64LittleToHost( arg: UInt64 ): UInt64;
begin
  CFSwapInt64LittleToHost := arg;
end;

function CFSwapInt16HostToLittle( arg: UInt16 ): UInt16;
begin
  CFSwapInt16HostToLittle := arg;
end;

function CFSwapInt32HostToLittle( arg: UInt32 ): UInt32;
begin
  CFSwapInt32HostToLittle := arg;
end;

function CFSwapInt64HostToLittle( arg: UInt64 ): UInt64;
begin
  CFSwapInt64HostToLittle := arg;
end;

function CFSwapInt16BigToHost( arg: UInt16 ): UInt16;
begin
  CFSwapInt16BigToHost := CFSwapInt16(arg);
end;

function CFSwapInt32BigToHost( arg: UInt32 ): UInt32;
begin
  CFSwapInt32BigToHost := CFSwapInt32(arg);
end;

function CFSwapInt64BigToHost( arg: UInt64 ): UInt64;
begin
  CFSwapInt64BigToHost := CFSwapInt64(arg);
end;

function CFSwapInt16HostToBig( arg: UInt16 ): UInt16;
begin
  CFSwapInt16HostToBig := CFSwapInt16(arg);
end;

function CFSwapInt32HostToBig( arg: UInt32 ): UInt32;
begin
  CFSwapInt32HostToBig := CFSwapInt32(arg);
end;

function CFSwapInt64HostToBig( arg: UInt64 ): UInt64;
begin
  CFSwapInt64HostToBig := CFSwapInt64(arg);
end;

function CFConvertFloat32HostToSwapped( arg: Float32 ): CFSwappedFloat32;
begin
  CFConvertFloat32HostToSwapped.v := CFSwapInt32(CFSwappedFloat32(arg).v);
end;

function CFConvertFloat32SwappedToHost( arg: CFSwappedFloat32 ): Float32;
begin
  CFConvertFloat32SwappedToHost := Float32(CFSwappedFloat32(CFSwapInt32(arg.v)));
end;

function CFConvertFloat64HostToSwapped( arg: Float64 ): CFSwappedFloat64;
begin
  CFConvertFloat64HostToSwapped.v := CFSwapInt64(CFSwappedFloat64(arg).v);
end;

function CFConvertFloat64SwappedToHost( arg: CFSwappedFloat64 ): Float64;
begin
  CFConvertFloat64SwappedToHost := Float64(CFSwappedFloat64(CFSwapInt64(arg.v)));
end;

function CFConvertFloatHostToSwapped( arg: Float32 ): CFSwappedFloat32;
begin
  CFConvertFloatHostToSwapped.v := CFSwapInt32(CFSwappedFloat32(arg).v);
end;

function CFConvertFloatSwappedToHost( arg: CFSwappedFloat32 ): Float32;
begin
  CFConvertFloatSwappedToHost := Float32(CFSwappedFloat32(CFSwapInt32(arg.v)));
end;

function CFConvertDoubleHostToSwapped( arg: Float64 ): CFSwappedFloat64;
begin
  CFConvertDoubleHostToSwapped.v := CFSwapInt64(CFSwappedFloat64(arg).v);
end;

function CFConvertDoubleSwappedToHost( arg: CFSwappedFloat64 ): Float64;
begin
  CFConvertDoubleSwappedToHost := Float64(CFSwappedFloat64(CFSwapInt64(arg.v)));
end;
{$endif}

{$endif}


{$ifdef INTERFACE}

// CFString.h
function CFSTR (c: PChar): CFStringRef; external name '___CFStringMakeConstantString'; overload;
function CFSTRP (c: PChar): CFStringRef; external name '___CFStringMakeConstantString';
function CFSTRP0 (c: PChar): CFStringRef; external name '___CFStringMakeConstantString';
function CFSTR (c: string): CFStringRef; overload;

// CFBase.h
function CFRangeMake( loc: CFIndex; len: CFIndex ): CFRange; external name '___CFRangeMake';

// CFBundle.h
function CFCopyLocalizedString( key: CFStringRef; comment: PChar ): CFStringRef;
function CFCopyLocalizedStringFromTable( key: CFStringRef; tableName: CFStringRef; comment: PChar ): CFStringRef;
function CFCopyLocalizedStringFromTableInBundle( key: CFStringRef; tableName: CFStringRef; bundle: CFBundleRef; comment: PChar ): CFStringRef;
function CFCopyLocalizedStringWithDefaultValue( key: CFStringRef; tableName: CFStringRef; bundle: CFBundleRef; value: CFStringRef; comment: PChar ): CFStringRef;
{$endif}

{$ifdef IMPLEMENTATION}
function CFSTR (c: string): CFStringRef; overload;
begin
	c := c+''+chr(0);
	result := CFSTRP(@c[1]);
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
{$endif}

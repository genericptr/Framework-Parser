
{$ifdef INTERFACE}
function NSSTR (inString: PChar): NSString; overload;
function NSSTR (inString: string): NSString; overload;
function NSMakeRange (loc: NSUInteger; len: NSUInteger): NSRange;
function NSMaxRange (range: NSRange): NSUInteger;
//function NSLocationInRange (loc: NSUInteger; range: NSRange): boolean;
//function NSEqualRanges (range1, range2: NSRange): boolean;
{$endif}

{$ifdef IMPLEMENTATION}
function NSMakeRange (loc: NSUInteger; len: NSUInteger): NSRange;
begin
  result.location := loc;
  result.length := len;
end;

function NSMaxRange (range: NSRange): NSUInteger;
begin
  result := range.location + range.length;
end;

{function NSLocationInRange (loc: NSUInteger; range: NSRange): boolean;
begin
  result := (loc <= range.location + range.length) and (loc >= range.location);
end;

function NSEqualRanges (range1, range2: NSRange): boolean;
begin
  result := (range1.location = range2.location) and (range1.length = range2.length);
end;}

function NSSTR (inString: PChar): NSString;
begin
  result := NSString(CFSTR(inString));
end;

function NSSTR (inString: string): NSString;
begin
	if inString <> '' then
  	result := NSString(CFSTR(@inString[1]))
	else
		result := NSString(CFSTR(''));
end;
{$endif}
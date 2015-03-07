{$ifdef INTERFACE}
function CGPointMake(x: CGFloat; y: CGFloat): CGPoint;
function CGSizeMake(width: CGFloat; height: CGFloat): CGSize;
function CGRectMake(x: CGFloat; y: CGFloat; width: CGFloat; height: CGFloat): CGRect;
{$endif}

{$ifdef IMPLEMENTATION}
function CGPointMake(x: CGFloat; y: CGFloat): CGPoint;
begin
  result.x := x;
  result.y := y;
end;

function CGSizeMake(width: CGFloat; height: CGFloat): CGSize;
begin
  result.width := width;
  result.height := height;
end;

function CGRectMake(x: CGFloat; y: CGFloat; width: CGFloat; height: CGFloat): CGRect;
begin
  result.origin.x := x;
  result.origin.y := y;
  result.size.width := width;
  result.size.height := height;
end;
{$endif}
{$if MAC_OS_X_VERSION_10_7 <= MAC_OS_X_VERSION_MAX_ALLOWED}
const
	kCFCalendarUnitWeekOfMonth = 1 shl 12;
	kCFCalendarUnitWeekOfYear = 1 shl 13;
	kCFCalendarUnitYearForWeekOfYear = 1 shl 14;
{$endif}
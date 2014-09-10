
/*

type
	MIDISysexSendRequestPtr = ^MIDISysexSendRequest;
	MIDICompletionProc = function (request: MIDISysexSendRequestPtr): pointer; cdecl;
  MIDISysexSendRequest = record
    completionProc: MIDICompletionProc;
  end;
	
*/

typedef void
(*MIDICompletionProc)(MIDISysexSendRequest *request);

struct MIDISysexSendRequest
{
	MIDICompletionProc	completionProc;
};


// duplicate ProcessInfoRecPtr because it's not in the same scope
/*
#if __LP64__
struct ProcessInfoRec {
  UInt32              processInfoLength;
};
typedef struct ProcessInfoRec           ProcessInfoRec;
#else
struct ProcessInfoRec {
  UInt32              processInfoLength;
};
typedef struct ProcessInfoRec           ProcessInfoRec;
#endif
typedef ProcessInfoRec *                ProcessInfoRecPtr;
*/
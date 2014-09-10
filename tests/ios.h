
// type order conflicts
// these need to get merged into a single block actually
// this may need to be done by hand...
/*
type
  MIDISysexSendRequestPtr = ^MIDISysexSendRequest;
	MIDICompletionProc = function (request: MIDISysexSendRequestPtr): pointer; cdecl;
  MIDISysexSendRequest = record
    destination: MIDIEndpointRef;
    data: BytePtr;
    bytesToSend: UInt32;
    complete: Boolean;
    reserved: array[0..2] of Byte;
    completionProc: MIDICompletionProc;
    completionRefCon: pointer;
  end;
*/
typedef struct MIDISysexSendRequest		MIDISysexSendRequest;
typedef void
(*MIDICompletionProc)(MIDISysexSendRequest *request);
struct MIDISysexSendRequest
{
	MIDIEndpointRef		destination;
	const Byte *		data;
	UInt32				bytesToSend[SOME_VALUE];
	Boolean				complete[2][10];
	Byte				reserved[3];
	MIDICompletionProc	completionProc;
	void *				completionRefCon;
};

// nested struct with array type!
/*
type
  AUDistanceAttenuationData = record
    inNumberOfPairs: UInt32;
    pairs: array[0..1] of record
      inDistance: Float32;
      outGain: Float32;
    end;
  end;
*/
typedef struct AUDistanceAttenuationData
{
	UInt32	 inNumberOfPairs;
	struct {
		Float32	inDistance;
		Float32 outGain;
	} pairs[1];
} AUDistanceAttenuationData;

// c++???
typedef struct AudioQueueBuffer {
    const UInt32                    mAudioDataBytesCapacity;
#ifdef __cplusplus
    AudioQueueBuffer() : mAudioDataBytesCapacity(0), mAudioData(0), mPacketDescriptionCapacity(0), mPacketDescriptions(0) { }
#endif
} AudioQueueBuffer;

// GK_EXTERN_WEAK needs to be added to the front of the array
// so it has precedence over parent frameworks types
GK_EXTERN_WEAK NSString *GKErrorDomain;

// extra Ptr on AudioBufferList which is implicit I thought
struct AudioBufferList
{
    UInt32      mNumberBuffers;
    AudioBuffer mBuffers[1];
};
typedef struct AudioBufferList  AudioBufferList;
typedef OSStatus	
(*AudioUnitProcessMultipleProc) (void *self, AudioUnitRenderActionFlags *ioActionFlags, const AudioTimeStamp *inTimeStamp, 
									UInt32 inNumberFrames, UInt32 inNumberInputBufferLists, const AudioBufferList **inInputBufferLists,
									UInt32 inNumberOutputBufferLists, AudioBufferList **ioOutputBufferLists);

// make ignore types take regex if wrapped in []?
// or don't define ignored types will work and probably block lots of other types like CM_INLINE static for example
// <ignore_types>[__AVAILABILITY_INTERNAL__\w+]</ignore_types>
#ifndef __AVAILABILITY_INTERNAL__MAC_10_7
	#define __AVAILABILITY_INTERNAL__MAC_10_7 __AVAILABILITY_INTERNAL_WEAK_IMPORT
#endif
// Pre-4.3, weak import
#ifndef __AVAILABILITY_INTERNAL__IPHONE_4_3
	#define __AVAILABILITY_INTERNAL__IPHONE_4_3 __AVAILABILITY_INTERNAL_WEAK_IMPORT
#endif
// Pre-10.8, weak import
#ifndef __AVAILABILITY_INTERNAL__MAC_10_8
	#define __AVAILABILITY_INTERNAL__MAC_10_8 __AVAILABILITY_INTERNAL_WEAK_IMPORT
#endif

// are #elif getting parsed? I think not....
#if !defined(CM_INLINE)
	#if defined(__STDC_VERSION__) && __STDC_VERSION__ >= 199901L
		#define CM_INLINE static inline
	#elif defined(__MWERKS__) || defined(__cplusplus)
		#define CM_INLINE static inline
	#elif defined(__GNUC__)
		#define CM_INLINE static __inline__
	#elif defined(_MSC_VER)
		#define CM_INLINE static __inline
	#else
		#define CM_INLINE static    
	#endif
#endif

// in c these are macros which replace text in the program
// can we mimic them in pascal safely?
// I think the problem is the macro needs to exist in every unit
// so we would need to include the file as {$i CV_macros} for example
// we could start making a comment symbol to add these back as notes to the reader at least....
// {$macro on}
// {$define kCVImageBufferFieldDetailTemporalBottomFirst := kCMFormatDescriptionFieldDetail_TemporalBottomFirst}
typedef int kCVImageBufferFieldDetailTemporalBottomFirst;
#define kCMFormatDescriptionFieldDetail_TemporalBottomFirst		kCVImageBufferFieldDetailTemporalBottomFirst

// this needs to be set IN the current header since
// in this case it only appears here!
//
// we may need to make a notification center for PHP since it will
// be pretty hard to get the module back to the symbol
#define GL_API extern

GL_API void         GL_APIENTRY glActiveTexture (GLenum texture);
GL_API void         GL_APIENTRY glAttachShader (GLuint program, GLuint shader)  __OSX_AVAILABLE_STARTING(__MAC_NA,__IPHONE_3_0);
GL_API void         GL_APIENTRY glBindAttribLocation (GLuint program, GLuint index, const GLchar* name)  __OSX_AVAILABLE_STARTING(__MAC_NA,__IPHONE_3_0);

// we need to get these type blocks under control...
struct SFNTLookupTable {
  SFNTLookupTableFormat  format;              
  SFNTLookupFormatSpecificHeader  fsHeader;   
};
typedef struct SFNTLookupTable          SFNTLookupTable;
typedef SFNTLookupTable *               SFNTLookupTablePtr;
typedef SFNTLookupTablePtr *            SFNTLookupTableHandle;

// %%PREGEX_NAMES%% pattern will include (CoreFoundation/CF into)
CF_EXTERN_C_BEGIN
CF_EXPORT
CFTypeID CFTimeZoneGetTypeID(void);
CF_EXTERN_C_END

// tons of missing functions from these
// one solution is pre-parse <extern_blocks> and prepend function/variables
// in the block with "extern" so the normal parser gets them
//
// maybe we could just make replacment patterns do this?
// 

// \w+_EXTERN_C_BEGIN
// \w+_EXTERN_C_END
// __BEGIN_DECLS
// __END_DECLS

#if defined(__cplusplus)
extern "C" {
#endif

extern GLKVector3 GLKMathProject(GLKVector3 object, GLKMatrix4 model, GLKMatrix4 projection, int *viewport);
GLKVector3 GLKMathProject(GLKVector3 object, GLKMatrix4 model, GLKMatrix4 projection, int *viewport);
GLKVector3 GLKMathUnproject(GLKVector3 window, GLKMatrix4 model, GLKMatrix4 projection, int *viewport, bool *success);

NSString * NSStringFromGLKMatrix2(GLKMatrix2 matrix);
NSString * const NSCharacterConversionException;

#if defined(__cplusplus)
}
#endif

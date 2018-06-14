

#define _DEFINED_OGRSpatialReferenceH

#define SRS_WKT_WGS84 "GEOGCS[\"WGS 84\",DATUM[\"WGS_1984\"]"

#ifndef _DEFINED_OGRSpatialReferenceH
int CPL_DLL CPL_STDCALL GDALGetDataTypeSize( GDALDataType );
#endif

struct {
       int     nMarker1;
       int     nMarker2;
} set;

#if defined(__cplusplus) && !defined(CPL_SUPRESS_CPLUSPLUS)
class CPL_DLL OGREnvelope
{
};
#else
typedef struct
{
   double      set;
   double      type;
   double      function;
   double      MaxY;
} OGREnvelope;
#endif

// no return type with macros
int CPL_DLL CPL_STDCALL GDALGetDataTypeSize( GDALDataType );

//  broken nested callbacks
extern int CPhidget_set_OnDetach_Handler(CPhidgetHandle phid, int( *fptr)(CPhidgetHandle phid, void *userPtr), void *userPtr);

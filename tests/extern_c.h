#if defined(__cplusplus)
extern "C" {
#endif
typedef struct __SecTask *SecTaskRef;

// comment
OSStatus SecTrustSetOCSPResponse(SecTrustRef trust, CFTypeRef responseData)
    __OSX_AVAILABLE_STARTING(__MAC_10_9, __IPHONE_7_0);

// comment
CFArrayRef SecTrustCopyProperties(SecTrustRef trust)
    __OSX_AVAILABLE_STARTING(__MAC_10_7, __IPHONE_2_0);

// comment
CFDictionaryRef SecTrustCopyResult(SecTrustRef trust)
    __OSX_AVAILABLE_STARTING(__MAC_10_9, __IPHONE_7_0);

#if defined(__cplusplus)
}
#endif

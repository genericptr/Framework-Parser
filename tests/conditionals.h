
# if __CG_HAS_COMPILER_ATTRIBUTE(deprecated) && !defined(CG_BUILDING_CG)
//#if defined(MACRO) && (!defined MACRO || (MACRO => 199901L))
	typedef unsigned foo;
#endif


#if (__BLOCKS__ && (MAC_OS_X_VERSION_10_6 <= MAC_OS_X_VERSION_MAX_ALLOWED || __IPHONE_4_0 <= __IPHONE_OS_VERSION_MAX_ALLOWED)) || __BLOCKS__
	typedef unsigned foo;
#endif

# if __CG_HAS_COMPILER_ATTRIBUTE(deprecated) && !defined(CG_BUILDING_CG)
	typedef unsigned foo;
#endif

# if defined(__STDC_VERSION__) && __STDC_VERSION__ >= 199901L
#  define FOO inline
#endif

#if defined(__GNUC_MINOR__) && (((__GNUC__ == 3) && (__GNUC_MINOR__ <= 3)) || (__GNUC__ < 3))
		typedef unsigned foo;
#endif

#if defined _AltiVecPIMLanguageExtensionsAreEnabled || defined __SSE__
		typedef unsigned foo;
#endif


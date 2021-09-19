

// ✅ DISPATCH_OPTIONS

DISPATCH_OPTIONS(dispatch_block_flags, unsigned long,
        DISPATCH_BLOCK_BARRIER = 0x1,
        DISPATCH_BLOCK_DETACHED = 0x2,
        DISPATCH_BLOCK_ASSIGN_CURRENT = 0x4,
        DISPATCH_BLOCK_NO_QOS_CLASS = 0x8,
        DISPATCH_BLOCK_INHERIT_QOS_CLASS = 0x10,
        DISPATCH_BLOCK_ENFORCE_QOS_CLASS = 0x20,
);

/*
DISPATCH_OPTIONS(dispatch_block_flags, unsigned long,
        DISPATCH_BLOCK_BARRIER
                        DISPATCH_ENUM_API_AVAILABLE(macos(10.10), ios(8.0)) = 0x1,
        DISPATCH_BLOCK_DETACHED
                        DISPATCH_ENUM_API_AVAILABLE(macos(10.10), ios(8.0)) = 0x2,
        DISPATCH_BLOCK_ASSIGN_CURRENT
                        DISPATCH_ENUM_API_AVAILABLE(macos(10.10), ios(8.0)) = 0x4,
        DISPATCH_BLOCK_NO_QOS_CLASS
                        DISPATCH_ENUM_API_AVAILABLE(macos(10.10), ios(8.0)) = 0x8,
        DISPATCH_BLOCK_INHERIT_QOS_CLASS
                        DISPATCH_ENUM_API_AVAILABLE(macos(10.10), ios(8.0)) = 0x10,
        DISPATCH_BLOCK_ENFORCE_QOS_CLASS
                        DISPATCH_ENUM_API_AVAILABLE(macos(10.10), ios(8.0)) = 0x20,
);



API_AVAILABLE(macos(10.12), ios(10.0), tvos(10.0), watchos(3.0))
DISPATCH_EXPORT DISPATCH_NONNULL1
void
dispatch_assert_queue(dispatch_queue_t queue)
        DISPATCH_ALIAS_V2(dispatch_assert_queue);


// ✅ DISPATCH_ENUM
DISPATCH_ENUM(dispatch_autorelease_frequency, unsigned long,
        DISPATCH_AUTORELEASE_FREQUENCY_INHERIT DISPATCH_ENUM_API_AVAILABLE(
                        macos(10.12), ios(10.0), tvos(10.0), watchos(3.0)) = 0,
        DISPATCH_AUTORELEASE_FREQUENCY_WORK_ITEM DISPATCH_ENUM_API_AVAILABLE(
                        macos(10.12), ios(10.0), tvos(10.0), watchos(3.0)) = 1,
        DISPATCH_AUTORELEASE_FREQUENCY_NEVER DISPATCH_ENUM_API_AVAILABLE(
                        macos(10.12), ios(10.0), tvos(10.0), watchos(3.0)) = 2,
);


DISPATCH_DECL(dispatch_queue);
DISPATCH_DECL_SUBCLASS(dispatch_queue_global, dispatch_queue);

*/
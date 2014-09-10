
/*

	NOTE: (+) copyWithZone/mutableCopyWithZone in NSObject has a conflict with a protocol that COULD
	interfer so we need a global array of rename methods like at first.....
*/


// turn bit fields on/off by adding to record
/*
_anoninternstruct_CAConstraintLayoutManager0: record
  case byte of
   0: (_anonbitfield_CAConstraintLayoutManager0: CAConstraintAttribute);
   1: (data: bitpacked record
    _srcAttr: 0..((1 shl 16)-1);
    _attr: 0..((1 shl 16)-1);
   end;
  );

*/

@interface CAConstraint : NSObject <NSCoding>
{
@private
  NSString *_srcId;
  CAConstraintAttribute _srcAttr :16;
  CAConstraintAttribute _attr :16;
  CGFloat _scale, _offset;
};

@end

typedef struct {
    signed   int _exponent:8;
    unsigned int _length:4;
    unsigned int _isNegative:1;
    unsigned int _isCompact:1;
    unsigned int _reserved:18;
// TURN OFF HERE!
    unsigned short _mantissa[NSDecimalMaxSize];
} NSDecimal;

/*

// reverse this so the ivars change name and the methods are protected
@interface UIAcceleration : NSObject {
  @private
    NSTimeInterval timestamp;
    UIAccelerationValue x, y, z;
}

@property(nonatomic,readonly) NSTimeInterval timestamp;
@property(nonatomic,readonly) UIAccelerationValue x;
@property(nonatomic,readonly) UIAccelerationValue y;
@property(nonatomic,readonly) UIAccelerationValue z;

@end

// struct needs to use the ALIAS name (_attr) not struct name
// also referencing the class causes a bug in FPC (mask: CALayer)
@interface CALayer : NSObject <NSCoding, CAMediaTiming>
{
@private
  struct _CALayerIvars {
    int32_t refcount;
    uint32_t flags;
    uintptr_t parent;
    CALayerArray *sublayers;
    CALayer *mask;
    struct _CALayerState *state;
    struct _CALayerState *previous_state;
    struct _CALayerAnimation *animations;
    uintptr_t slots[3];
#if defined (__LP64__) && __LP64__
    uint32_t reserved;
#endif
  } _attr;
}
@end


*/

/*

// external blocks
// these are in various places and sometimes wrap
// long blocks of code besides just variables
extern "C" {
NSString *ABCreateStringWithAddressDictionary(NSDictionary *address, BOOL addCountryName);
}

*/

// single line struct
// we could prevent the start from offset idea if the parser
// took the open scopes end pattern as priority even though fields may come after
struct struct_basic_single {int field; int field2; int field3} struct_basic_single_alias;	// comment 3

// nested struct
struct nested_struct {
	signed   int	field1;
	unsigned int	field2;
	unsigned int	field3;
#if NS_BLOCKS_AVAILABLE
	struct {
		signed   int	field1;
		unsigned int	field2;
		unsigned int	field3;
	} field4;
#endif
	unsigned int	field5;
	unsigned int	field6;
};

// opaque type to struct which doesn't exist
typedef struct CATransform3D CATransform3D;

// unamed structs and multi-dimensional arrays
union _GLKMatrix2
{
    struct
    {
        float m00, m01;
        float m10, m11;
    };
    float m[4][4];
};

/*

// struct with bit-alignemtn fields
// and array field which uses constant value 
// from NSDecimal.h
#define NSDecimalMaxSize (8)
typedef struct {
    signed   int _exponent:8;
    unsigned int _length:4;
    unsigned int _isNegative:1;
    unsigned int _isCompact:1;
    unsigned int _reserved:18;
    unsigned short _mantissa[NSDecimalMaxSize];
} NSDecimal;

#if NS_BLOCKS_AVAILABLE
struct skip_struct {
	signed   int	field1;
	unsigned int	field2;
	unsigned int	field3;
};
#endif

// struct with callback fields
typedef struct {
    NSUInteger	(*hash)(NSHashTable *table, const void *);
    BOOL	(*isEqual)(NSHashTable *table, const void *, const void *);
    void	(*retain)(NSHashTable *table, const void *);
    void	(*release)(NSHashTable *table, void *);
    NSString 	*(*describe)(NSHashTable *table, const void *);
} NSHashTableCallBacks;

// basic struct
struct struct_basic
{
	int		field1;		// comment 1
	int		field2;		// comment 2
	int		*field3;
#if __LP64__
	int		field4[4];
#endif	
};

// inline 
struct {
    short subtype;
    BOOL gestureEnded;
    BOOL reserved;
    int value;
    float percentage;
#if __LP64__
    int reserved2[7];
#endif
} miscGesture;
// same as "var structvar: record <struct_definition> end;"

typedef struct {
	int field1;
	int field2;
} typdef_struct_name_end;

typedef struct typdef_struct_name_beginning {
	int field1;
	int field2;
};

// typedef struct with aliases
typedef struct struct_with_alias
{
	int		field1;		// comment 1
	int		field2;		// comment 2
} struct_with_alias_1, struct_with_alias_2, *struct_with_alias_3;

struct CATransform3D
{
 CGFloat m11, m12, m13, m14;
 CGFloat m21, m22, m23, m24;
 CGFloat m31, m32, m33, m34;
 CGFloat m41, m42, m43, m44;
};

// typedef struct with multiple other typedefs and pointer
typedef struct typdef_struct {
	int		field1;
	int		field2;
} alias_1_typdef_struct, alias_2_typdef_struct, *alias_3_typdef_struct;

// variable definitions
//struct { <struct_definition> } structvar; // same as "var structvar: record <struct_definition> end;"
//typedef struct { <struct_definition> } aliasname; // same as (***) above, except that you can only use "aliasname" to refer to this type, rather than also "struct structname"

*/
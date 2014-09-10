// IMPORTANT: currently only /ES1/gl.h is parsed but
// GLKit.framework requires ES2 so we fake it by adding some
// support types
const
	GL_TEXTURE_CUBE_MAP = $8513;
	
type
	GLchar = char;
	GLcharPtr = ^GLchar;
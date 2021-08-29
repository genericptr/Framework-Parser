

/*
 
  
 4. Geometry of the lens
 
    This is a thin lens model.
 
    @property focalLength
 
    The default focal length is 50mm, corresponding to a field of view of 54 
    degrees, and vertical sensor aperture of 24mm. Changing focalLength will 
    update the field of view property.
 
    @property focusDistance
 
    The distance, in meters, at which the lens is focused. The default is 2.5m.
 
    @property fieldOfView
 
    The field of view is calculated from the focal length and sensor aperture.
    Changing the field of view will update the focalLength property with respect 
    to the sensor aperture. The default is 54 degrees, corresponding to a focal 
    length of 50mm, and a vertical sensor aperture of 24mm.
 
    @property fStop
 
    The f-stop is the ratio of the lens' focal length to the diameter of the 
    entrance pupil. The default is 5.6. It controls the amount of light that 
    reaches the sensor, as well as the size of out of focus parts of the image.
    The diameter of the entrance pupil, is therefore obtained
    by dividing the fStop by the focalLength.
 
 5. Lens exit aperture
 
    @property apertureBladeCount
 
    The shape of out of focus highlights in a scene is commonly known as "bokeh". 
    The aesthetic quality of a lens' bokeh is one of the characteristics that 
    drives the choice of a lens for a particular scene. To a large degree, the 
    appearance of bokeh is governed by the shape of the lens aperture. Typical 
    lens apertures are controlled by a series of overlapping blades that can be 
    irised open and closed. A lens with a five blade aperture will yield a five
    sided bokeh. The default is zero, which is to be interpreted as a perfectly 
    round aperture.
 
    Note that the effect of a filter on the front of the lens can be modeled 
    equivalently at the exit aperture. The MIOCamera does not explicitly provide 
    specification of such effects, but a simulation could incorporate them at 
    this stage.
 
    @property maximumCircleOfConfusion

    Although the size of an out of focus bokeh highlight can be computed from
    other camera properties, it is often necessary to limit the size of the 
    circle of confusion for aesthetic reasons. The circle of confusion is 
    specified in mm, and the default is 0.05mm. The units are mm on the sensor 
    plane.
 
    @property shutterOpenInterval
 
    The length of time in seconds the shutter is open, impacting the amount of 
    light that reaches the sensor and also the length of motion blur trails. The 
    shutter time is not the same thing as scene frame rate. The rule of thumb for 
    movies is that the shutter time should be half the frame rate, so to achieve
    a "filmic" look, the shutter time choice might be 1/48 of a second, since 
    films are usually projected at 24 frames per second. Shutter time is 
    independent of simulation frame rate because motion blur trails and exposure 
    times should be held constant in order to avoid flicker artifacts.
 
 6. Sensor illuminance
 
    @property sensorVerticalAperture
 
    The default aperture is 24mm, corresponding to a 35mm stills camera.
    _____________________
    [][][][][][][][][][][              ^
     \     |         |  \      ^       |
      \    |         |   \    24mm   35mm
       \   |         |    \    v       |
       ][][][][][][][][][][]           v
       ---------------------
           <- 36mm -->
 
    Changing the aperture will update the field of view property with respect to 
    the focal length. The horizontal aperture can be calculated from the aspect 
    property.
 
    @property sensorAspect
    aspect ratio of width versus height of the sensor aperture. The default is 
    36mm/24mm, ie: 1.5.
 
    @property sensorEnlargement

    The sensor enlargment property scales the active region of the sensor. A 
    zoom factor of 2 will result in the central portion of the image being 
    cropped and expanded to fill the image plane. The default is 1.
 
    @property sensorShift
    
    The sensor shift property moves the center of the sensor relative to the 
    lens. This is useful in certain calculations, such as shadow or reflection
    projections, as well as to provide vergence in a parallel stereoscopic camera.

 7. Exposure
 
    Finally, the sensor values need to be converted for display.
 
    @property flash
 
    Flashing is applied to a film stock some time before processing. It is a 
    small and even level of exposure added to the entire image intended shift 
    the brightness and color of darker areas of the image. Since exposure is 
    logarithmic, flash does not affect midtones or highlights.
    
    The equivalent to flash in a color grading system is known as lift. Typically 
    red, green, and blue can be controlled independently.
 
    A negative value for flash can be used to subtract color.
 
    @property exposure
 
    Finally, exposure should be applied to the compressed value. Red,
    green, and blue exposure can be specified separately. The default is 1.0.

    @property exposureCompression
 
    Gamma curve compression where values below the x value are to be passed through,
    about the y value, values are to be clamped at maximum display brightness,
    and a function such as a logarithmic ramp is to be applied in between. The
    default is (1,0, 1.0).

    A displayable value is therefore obtained via
       pow(exposureCompression((sensor value + flash) * exposure), displayGamma)

 */


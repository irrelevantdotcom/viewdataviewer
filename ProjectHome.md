This is a set of scripts to allow creation of a PNG or GIF image from a viewdata or teletext frame, on-the-fly.

A viewdata frame is typically 23-25 lines of 40 characters.  Most save formats are straight sequences of bytes, one per screen position.   Depending on the source, there may also be a header or trailer containing binary data.

As well as standard characters, loosely based on the ASCII character set, special control codes affect subsequent characters on the line in particular ways.  This includes changing colours, introducing graphics, and other special features.

Needless to say, this format is not therefore intelligible to any standard web browser.  The usual approach for displaying the data is therefore to convert it to a modern image format before uploading it to web server.   This can be a time consuming process, and results are typically very variable and, to date, invariably lose some detail, such as flashing characters.

These scripts can be used to process a teletext or viewdata screen file directly on the webserver, sending the browser either a PNG or an animated GIF, to fully support flashing characters.  In addition, and uniquely, a "text only" view is supported, as is a screen-reader friendly "Long description".

The default font file used is as close to that used in actual teletext hardware (SAA5050) as we can get, so giving images that are as true to life as possible.

Suggestions and requests for enhancements are always welcome.  If you have any saved pages/frames that display incorrectly, please include or link to a copy.


Please browse the source code for the latest versions - downloadable packages are lagging somewhat behind!


2nd March 2010.
I am starting on converting the script to a class file, along with a wrapper to ensure compatibility with the current version.  This should allow for much more flexibility, especially when using text mode.

7th February 2011.
Almost a year after writing the above, the conversion to a class is actually progressing.  Read my blog for updates.
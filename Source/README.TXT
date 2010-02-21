Viewdata / Teletext file viewer.
Copyright (c) 2010, Robert O'Donnell robert@irrelevant.com
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of irrelevant.com nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL MR O'DONNELL BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


Please note that you will require a font file.	You may use the files supplied
within the Font folder, however these are derived from a file supplied with
"BeebEm" and is licenced seperately. (GNU)

Please note that GIFEncoder.class.php is included for completeness.  Licencing
requirements for this are unclear as it was taken from a GIF tutorial web page.


THIS IS CURRENTLY A WORK-IN-PROGRESS AND AS SUCH MAY BE OUT OF DATE ALREADY!
PLEASE DO NOT RELY ON THIS IN A PRODUCTION ENVIRONMENT WITHOUT CAREFUL
CONSIDERATION OF THIS AND WITHOUT CHECKING FOR UPDATES FIRST.


Files included herein:
vv.php	- viewdata viewer- this is the main focus of the project.
vl.php	- gallery type lister - copy of script from my site that uses vv.php
vb.php  - simple viewdata browser (for SofMac/Gnome format frames)
GIFEncoder.class.php - Animated gif encoder (see comments for author)
fntfilecreator.bbc - program to create required GDF font files.

Suitable fonts are included in the fonts directory.  You may co-locate these
alongside vv.php, or leave them in the directory and adjust the references 
within vv.php to the correct path.

Please ensure you create a folder 'cache' in the same folder as vv.php
and allow write access to it.


vv.php - display a viewdata/teletext frame as an image
------------------------------------------------------
parameters:
gal=<folder>
page=<filename> OR text=<text to display>
offset=<bytes> default=0, skip <bytes> from start of file.
width=<columns>
height=<lines> (of source file, not incl. "top")
longdesc=<mode> 0(default)=graphic, 1=text, 2=<pre>text with * for graphics.
thumbnail=<tn size> 0(default)=full size, 0<n<3 = n*100 pixel wide image.
top=<text> optional line to insert above image (see also format b9)
format=<option bits>
--format b9 = "top" text overwrites top line in file.
--format b8 = attempts to be case insensitive on page->filenames.
--format b7 = disable black as a colour
--format b6 = disable writing rendered file to cache
--format b5 = disable checking cache for pre-rendered file
--format b4 = maps . in pagename to / in filename
--format bits 0-3  file type override

vl.php - display a web page showing all frames in a folder
----------------------------------------------------------
This should be treated as an example of how to use vv.php, as it is taken
from my website and therefore tailored towards that (and currently is in a
state of flux, anyway!)
parameters:
gal=<folder>
layout=<layout>  0=horizintal, 1=vertical
cols=<table cols> default 4/1 for horiz/vert
baseurl=<prefix>

If a file is found with a matching .txt file, that file is read to obtain
a title (line 1) and description (rest of lines).  The title is shown under
the thumbnail for horizontal view, and both are shown alongside it for
vertical view.

If a file "index.txt" is found in the gallery folder, then it is used to 
source titles and descriptions for any files without their own .txt
line format is
filename:title:description
or
filename+offset:title:description

This is intended to be used as a component frame to a larger web page.



fontfilecreator.bbc
-------------------
required BBC Basic For Windows.
http://www.compulink.co.uk/~rrussell/bbcwin/bbcwin.html
(This will work with free demo version.)

Required if you wish to use another font file to that supplied.


CHANGELOG
=========
New in vv0.5.2
text=parameter to create ad-hoc text!

new: vb0.1.2 = viewdata browser.  Very simple browser for gnome format pages.

New in vv0.5.3
format | 16 maps . in pagename to / in filename
format | 256 attempts to be case insensitive on page->filenames.

new in vb0.1.3
bug fix longdesc link.
construct top status line.

new in vv0.5.5
just tinkering to get frame recognition working better.

new in vv0.5.6
more tinkering with sofMac format recognition. Why did nobody put an easy id in...
Initial added support for RAW mode (format=3) (== .vdt files)

new in vb 0.1.5
add javascript to allow keypress selection of routes.

new in vl 0.3.1
tinker with layout and css support. only relevant for own use.
add index.txt file support. 
add baseurl option. Prefix all URLs in output with specified string.

new in vl 0.3.2
add pagination!  GET parameters  start=n qty=n
if qty is defined, first/prev/next/last links are displayed.
any non-script paramters are passed on within these links.

new in vl 0.3.3.
add zoom parameter to show full size version of one image. Paging optons then
 refer to next/prev image as required.  May be complete now for my useage.

new in vv0.5.7
Support for "Axis Microbase" datafiles.  recognition works. First frame
(incl. header) is at offset 4096 (defaulted).

new in vb0.1.7
Support for Axis microbase format datafiles for browsing.
Integration with existing websites via baseurl & passing on other GET paramaters

new in vv0.5.8
Support for !SVReader format files.

new in vb0.1.8
Support for !SVReader format files.
Bug fixes with website integration.
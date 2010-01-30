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
vv.php	- viewdata viewer
vl.php	- gallery type lister
GIFEncoder.class.php - Animated gif encoder (see comments for author)
fntfilecreator.bbc - program to create required GDF font files.

Suitable fonts are included in the fonts directory.  You may co-locate these alongside vv.php, or leave them in the directory and adjust the references within vv.php to the correct path.

Please ensure you create a folder 'cache' in the same folder as vv.php
and allow write access to it.


vv.php - display a viewdata/teletext frame as an image
s------------------------------------------------------
parameters:
gal=<folder>
page=<filename> OR text=<text to display>
width=<columns>
height=<lines>
format=<option bits>
--format b7 = disable black as a colour
--format b6 = disable writing rendered file to cache
--format b5 = disable checking cache for pre-rendered file
--format bits 0-3  file type override

vl.php - display a web page showing all frames in a folder
----------------------------------------------------------
parameters:
gal=<folder>
layout=<layout>  0=horizintal, 1=vertical
cols=<table cols> default 4/1 for horiz/vert

If a file is found with a matching .txt file, that file is read to obtain
a title (line 1) and description (rest of lines).  The title is shown under
the thumbnail for horizontal view, and both are shown alongside it for
vertical view.

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
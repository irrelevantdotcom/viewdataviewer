# Introduction #

 wip 

layout of .TT format files.


# Details #
```

Data is stored in 2048 byte blocks.
The final byte of each block indicates the number of frames stored in that block.
There will be a certain amount of junk at the end of each block.

Within each block -
Each frame is stored as 10 bytes header, _n_ byte semi-compressed page data, repeated.

Header:
0000-0001  length of frame including header
0002-0003  page number
0004-0009  ?
Page data:
000A-000D  ?
000E-000F  page number
000F-

 first few bytes are control data from teletext data stream.
data is odd-parity.  strip top bit for usable text.
multiple characters are replaced by 0x0f <count> <character>
```
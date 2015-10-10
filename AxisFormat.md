# Introduction #

Axis Microbase, (C) K Waddon 1988, allowed an off-line viewing of a database of frames.

Deduced format sufficient for extraction of files from a database as used by a 1989 PC+ Demonstration disc.

Database format is:
```
0000-000F  - ? 16 bytes data
0010-0FFF  - space for 340 frame location records.

  Each frame location record consists of 
  Ten bytes frame name, space filled, e.g. "80011001a "
  Two bytes frame ID, low byte first.

frame records are in the same order as frames following. ID numbers are not
consecutive or starting at zero.

1000-56FFF   - Frames.  Each frame occupies &400 (1024) bytes.

Frame format:
0000-0001   00 "F"
0002-000B   ten bytes frame name, space filled, as above.
000C-0029   null filled
002A-003F   eleven routing entries. Each consist of 2 bytes with ID number of
            frame for this route. Correspond to routes 0-9 and Hash
0040-03D7   920 bytes (23 x 40) character data. Control codes are stored as the
            letter they relate to with top bit set. E.g. Esc F is stored as C6.
03D8-03FF   null filled.
```
Alternate frame format:
```
0000-0001   F0 "i"
0002-000B   ten bytes frame name, left justified, space filled.
000C-0011   00 40 00 18 00 02 ?
0012-0021   null filled
0022-0053   ten routing table entries.  Each consists of five bytes. Destination
            page number is encoded *** BCD *** left justified, "F" filled. so, e.g.
            a destination of 8007023a is encoded as 80 07 02 3F FF.  Weird!!
0054-005B   "00000000"
005C-006C   null filled
0068-03FF   920 bytes character data as above.
```

Entire data set above is repeated every 352256 (&56000) bytes to accommodate more than 340 frames per file!

Read each page/id table into an array. Offset to the page data for the _$n_ th entry in that array can be calculated by:
```
		$offset=4096+1024*$n;
		$offset += 4096*(int)($offset/352256);
```
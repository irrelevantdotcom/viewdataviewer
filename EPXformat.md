# Introduction #

Format of files with file extension .EPX

Only two examples received so far.

# Details #

File header:
```
0000-0002   "JWC" 
0003        No. of pages in file
0004-0005   00 00
```
Frame data, repeated:
```
0000-0005    FE 01 09 00 00 00  ?
0006-03EE    1000 bytes frame data (40 x 25 line screen, top bit low graphics chars)
03EF-03FF    00 00
```

Files with the extension .EP1 consist of a single frame data record only.
# Introduction #

!SVReader is/was a RISC OS demo of Silicon Village and included a selection of sample pages stored as individual files within the application's directory.

!SVReader stored it's pages in subfolders, so that page "abcdx" is stored in folder "&.a.b.c", however this has no relevance to the actual file format.

# Details #

 This is a work in progress 

```
0000-0009   10 bytes page name, right justified, space filled.
000A        1 byte "Y" ?
000B-000F   5 bytes CUG right justified zero filled. 99999 = open?
0010-0014   5 bytes "     " ?
0015        1 byte - automatically chain next frame "Y" 
0016        1 byte "Y" ?
0017-0028   ?
0029-0031   9 bytes "802802802" - owner?

0032-008B   10 x 9 bytes route. Right justified, space flled.
008C-0093   8 bytes "        " ?
0094-0096   4 bytes "000" ?
0097        1 byte " " ?
0098-00B3   28 bytes - frame description
00B4-00BC   9 bytes. Another page id?  Right justified, space filled.
00BD        1 byte "i" - frame type?

00BE - Eof  Page frame data.
```

Frame data is ASCII, colour control codes stored as 80-9F, short lines terminated CRLF.

One of those Ys is probably the NUA flag..
# Introduction #

This is a LAN based network package for acorn machines.  Multiple frames are stored within a single file.


# Details #

1K block repeated for each frame in the file:

40 characters x 23 lines = 920 bytes

104 bytes control information.

<pre>       Teletext Page Data Format<br>
<br>
Byte<br>
000-919 Screen data<br>
920     Version byte    13: Version 0.5<br>
2: Version 1.0  3: Version 2.0<br>
4: Version 4.nn 5: Version 5.nn<br>
921-933 Page title<br>
934     Flag byte 2<br>
935     Flag byte1 : b7  Not use timer<br>
b6  Display clock<br>
b5  Clear screen<br>
b4-b0 Number of frames<br>
936-999 Links (4 bytes each)<br>
936-975 Link 0 to link 9 (page links)<br>
976-979 Link 10 (return link) (^)<br>
980-983 Link 11 (follow-on link) (SPACE)<br>
984-999 Links 12 to 15 (Unused)<br>
0 means no link<br>
-1 means link to menu (page 0)<br>
1000-1001 Carousel timer, centiseconds<br>
1002-1023 Spare<br>
***************************************<br>
</pre>
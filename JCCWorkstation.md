# Introduction #

Format of the data files used by the John Clarke Computing "Workstation" DOS programme.   Currently only data files held are for "Healthdata" offline database.


# Details #

VIEWDATA.IDX file:
```
9 byte records
0000-0004  page number
0005-0008  offset within VIEWDATA.PIC file.
```

VIEWDATA.PIC file:

variable length records
starting point pointed to by IDX file.
terminated by &FF byte.
```
0000-0009
000A - 006C  10 off 9 byte routing entries 0-9. Right justified Zero filled.
006D - eof   frame contents, terminated by &FF, short lines terminated CRLF

```
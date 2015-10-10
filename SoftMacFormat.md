# Introduction #

This is the format used by the Autognomic viewdata host, CommSoft, and other BBC Micro based software.


# Details #

## Host frames ##

144 byte header

40 characters x 22 lines data. = 880 byes.

## Saved pages ##

104 byte header

40 characters x 23 lines data. = 920 byes.

## details ##

These are largely interchangeable.  The specification of the final 40 characters of the header closely matches the use of the top line on the Prestel viewdata service.


<pre>
|--------------------|<br>
0   | Flags              |<br>
|--------------------|<br>
1   | Free               |<br>
|--------------------|<br>
2   | Free               |<br>
|--------------------|<br>
3   | 5 Byte CUG         |  '     ' and '00000' are public<br>
|--------------------|<br>
8   | User Access        |  ASC 'y' or ASC 'n'<br>
|--------------------|<br>
9   | Frame Type         |  See below<br>
|--------------------|<br>
10  | 4 Byte Frame Cnt   |  or 2 bytes count, and 2 bytes price<br>
|--------------------|<br>
14  | Route 0, 9 bytes   |  Left justified, space padded,<br>
|--------------------|<br>
23  | Route 1, 9 bytes   |   a single asterisk if no route present<br>
|--------------------|<br>
32  | Route 2, 9 bytes   |<br>
|--------------------|<br>
41  | Route 3, 9 bytes   |<br>
|--------------------|<br>
50  | Route 4, 9 bytes   |<br>
|--------------------|<br>
59  | Route 5, 9 bytes   |<br>
|--------------------|<br>
68  | Route 6, 9 bytes   |<br>
|--------------------|<br>
77  | Route 7, 9 bytes   |<br>
|--------------------|<br>
86  | Route 8, 9 bytes   |<br>
|--------------------|<br>
95  | Route 9, 9 bytes   |<br>
|--------------------|<br>
104 | IP Header 24 bytes |  In top bit set format<br>
|--------------------|<br>
128 | owner,    10 bytes |  Mbx no of creator, page number on displayed/saved frames<br>
|--------------------|<br>
138 | Spare,    1 bytes  |<br>
|--------------------|<br>
139 | Edit CUG, 5 bytes  |  '00000' and '     ' are private.  Price on display/saved<br>
|--------------------|<br>
</pre>

Prestel frames generally have a price in the top right. col 39 has a lower case "p", cols 37-38 have the price in pence. This is either a numeric " 0" to "99" or the character "\" - this is a 1/2 character in the viewdata character set.  There will be a prefixing yellow\_text or red\_text control character.
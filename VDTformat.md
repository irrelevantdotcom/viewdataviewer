# Introduction #

ConnectVX for Windows saves files with a .vdt file extension format.


# Details #

This is a 'raw' format viewdata file.  It consists of a byte stream as-received from the viewdata host. i.e. control sequences are introduced by an Escape character (27) and lines are terminated with a CR/LF (13/10) sequence.

vv.php auto detects this based on the presence of at least one CR/LF (or LF/CR) sequence within the file.  Use format |=3 to force.
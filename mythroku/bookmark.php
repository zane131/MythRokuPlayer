<?php

include 'db_utils.php';

// Expected parameters in $_GET:

// 'act' => 'get' or 'set'
if ( !isset($_GET['act']) or
     ('get' != $_GET['act'] and 'set' != $_GET['act']) )
{
    die( "Parameter 'act' not valid." );
}

// If 'act' is 'set',
//    'sec' => The timestamp of the bookmark in seconds.
if ( ('set' == $_GET['act']) and !isset($_GET['sec']) )
    die( "Parameter 'sec' not valid." );

// 'type' => 'rec' or 'vid'
if ( !isset($_GET['type']) or
     ('rec' != $_GET['type'] and 'vid' != $_GET['type']) )
{
    die( "Parameter 'type' not valid." );
}

// If 'type' is 'rec'
//    'chanid' => The channel ID.
//    'start'  => The actual start time of recording.
if ( ('rec' == $_GET['type']) and
     (!isset($_GET['chanid']) or !isset($_GET['start'])) )
{
    die( "Parameters 'chanid' and/or 'start' not valid." );
}

// If 'type' is 'vid'
//    'file' => Filename of video.
if ( ('vid' == $_GET['type']) and !isset($_GET['intid']) )
    die( "Parameter 'intid' not valid." );


switch ( $_GET['type'] )
{
    case 'rec':
        switch ( $_GET['act'] )
        {
            case 'get':
                getBookmarkRec( $_GET['chanid'], $_GET['start'] );
                break;
            case 'set':
                setBookmarkRec( $_GET['chanid'], $_GET['start'], $_GET['sec'] );
                break;
        }
        break;
    case 'vid':
        switch ( $_GET['act'] )
        {
            case 'get': getBookmarkVid( $_GET['intid'] );               break;
            case 'set': setBookmarkVid( $_GET['intid'], $_GET['sec'] ); break;
        }
        break;
}

//------------------------------------------------------------------------------

// This function uses 'mp4info' to get the frame rate of the video. If the user
// does have this installed, the function will use a default value based on the
// '$highRange' parameter. Videos tend to be between 24 and 30 frames/second. So
// to err on the side of caution, use the following rules:
//  - Use 'true' if you intend to divide the number of frames by the frame rate
//    to get the number of seconds (returns 30).
//  - Use 'false' if you intend to multiple the number of seconds by the frame
//    rate to get the number of frames (returns 24).

function getFrameRate( $dirname, $filename, $highRange )
{
    $framerate = 0;

    do
    {
        $fullPathName = "$dirname/$filename";

        $out = NULL;
        $rc = -1;

        exec( "mp4info $fullPathName", $out, $rc );
        if ( 0 != $rc ) break; // mp4info probably isn't installed.

        foreach ( $out as $line )
        {
            preg_match( "/video.*@ ([0-9]+\.[0-9]*) fps/i", $line, $matches );
            if ( $matches )
            {
                $framerate = $matches[1];
                break;
            }
        }

    } while (0);

    if ( 0 >= $framerate )
    {
        // Something failed so guess the frame rate.
        $framerate = $highRange ? 30 : 24;
    }

    return $framerate;
}

//------------------------------------------------------------------------------

function getBookmarkRec( $chanid, $act_start )
{
    $SQL = <<<EOF
SELECT A.basename AS filename, B.dirname, C.mark
FROM recorded A
    INNER JOIN storagegroup B ON A.storagegroup = B.groupname
    INNER JOIN recordedmarkup C
        ON A.chanid = C.chanid AND A.starttime = C.starttime AND C.type = '2'
WHERE A.chanid = '$chanid' AND A.starttime = '$act_start'

EOF;

    getBookmark( $SQL );
}

//------------------------------------------------------------------------------

function getBookmarkVid( $intid )
{
    $SQL = <<<EOF
SELECT A.filename, B.dirname, C.mark
FROM videometadata A
    INNER JOIN storagegroup B ON B.groupname = 'Videos'
    INNER JOIN filemarkup C   ON A.filename = C.filename AND C.type = '2'
WHERE A.intid = '$intid'

EOF;

    getBookmark( $SQL );
}

//------------------------------------------------------------------------------

function getBookmark( $SQL )
{
    $seconds = 0;

    $db_handle = opendb();

    $results = mysql_query( $SQL );

    switch ( mysql_num_rows($results) )
    {
        case 0: $seconds = 0; break;
        case 1:
            $db_field = mysql_fetch_assoc($results);
            $frames   = $db_field['mark'];
            $fps      = getFrameRate( $db_field['dirname'],
                                      $db_field['filename'], true );
            $seconds  = $frames / $fps;
            break;
        default:
            die( "[getBookmark] Multiple entries found:\n\n$SQL" );
    }

    closedb( $db_handle );

    echo intval( $seconds );
}

//------------------------------------------------------------------------------

function setBookmarkRec( $chanid, $act_start, $seconds )
{
    $db_handle = opendb();

    $SQL = <<<EOF
SELECT A.basename AS filename, B.dirname
FROM recorded A
    INNER JOIN storagegroup B ON A.storagegroup = B.groupname
WHERE A.chanid = '$chanid' AND A.starttime = '$act_start'

EOF;

    $results = mysql_query( $SQL );
    if ( 1 != mysql_num_rows($results) )
    {
        die( "[setBookmark] query failed:\n\n$SQL" );
    }

    $db_field = mysql_fetch_assoc($results);

    $fps = getFrameRate( $db_field['dirname'], $db_field['filename'], false );

    $frames = $seconds * $fps;

    $SQL = <<<EOF
SELECT *
FROM recordedmarkup
WHERE chanid = '$chanid' AND starttime = '$act_start' AND type = '2'

EOF;

    $results = mysql_query( $SQL );
    if ( 0 == mysql_num_rows($results) )
    {
        $SQL = <<<EOF
INSERT INTO recordedmarkup (chanid, starttime, mark, type)
VALUES ('$chanid', '$act_start', '$frames', '2')

EOF;
        mysql_query( $SQL );
    }
    else
    {
        $SQL = <<<EOF
UPDATE recordedmarkup
SET mark = '$frames'
WHERE chanid = '$chanid' AND starttime = '$act_start' AND type = '2'

EOF;
        mysql_query( $SQL );
    }

    closedb( $db_handle );
}

//------------------------------------------------------------------------------

function setBookmarkVid( $intid, $seconds )
{
    $db_handle = opendb();

    $SQL = <<<EOF
SELECT A.filename, B.dirname
FROM videometadata A
    INNER JOIN storagegroup B ON B.groupname = 'Videos'
WHERE A.intid = '$intid'

EOF;

    $results = mysql_query( $SQL );
    if ( 1 != mysql_num_rows($results) )
    {
        die( "[setBookmark] query failed:\n\n$SQL" );
    }

    $db_field = mysql_fetch_assoc($results);

    $fps = getFrameRate( $db_field['dirname'], $db_field['filename'], false );

    $frames = $seconds * $fps;

    $SQL = <<<EOF
SELECT *
FROM filemarkup
WHERE filename = '{$db_field['filename']}' AND type = '2'

EOF;

    $results = mysql_query( $SQL );
    if ( 0 == mysql_num_rows($results) )
    {
        $SQL = <<<EOF
INSERT INTO filemarkup (filename, mark, type)
VALUES ('{$db_field['filename']}', '$frames', '2')

EOF;
        mysql_query( $SQL );
    }
    else
    {
        $SQL = <<<EOF
UPDATE filemarkup
SET mark = '$frames'
WHERE filename = '{$db_field['filename']}' AND type = '2'

EOF;
        mysql_query( $SQL );
    }

    closedb( $db_handle );
}

?>

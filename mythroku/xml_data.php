<?php

function get_xml_data()
{
    $xml = '';

    require 'settings.php';
    require 'xml_utils.php';

    // Get any parameters from $_GET
    $type = '';
    if ( isset($_GET['type']) ) { $type = $_GET['type']; }
    switch ( $type )
    {
        case 'vid': case 'rec': break;
        default: die( "Invalid parameter: [type]=[$type]\n" );
    }

    $sort = '';
    if ( isset($_GET['sort']) ) { $sort = $_GET['sort']; }

    $start_row = 0;
    if ( isset($_GET['index']) ) { $start_row = $_GET['index']; }

    $test = false;
    if ( isset($_GET['test']) ) { $test = true; }

    // Make a connection to the mySQL server
    $db_handle = mysql_connect($MysqlServer, $MythTVdbuser, $MythTVdbpass);
    $db_found  = mysql_select_db($MythTVdb, $db_handle);
    if ( !$db_found )
    {
        die( 'Database NOT found' . mysql_error() . '\n' );
    }

    // Build SQL query
    $SQL = '';
    switch ( $type )
    {
        case 'vid': $SQL = build_query_vid( $sort ); break;
        case 'rec': $SQL = build_query_rec( $sort ); break;
    }

    // Get the full result count
    $sql_result = mysql_query($SQL);
    $total_rows = mysql_num_rows($sql_result);

    // Limit the number results
    if ( 0 !== $ResultLimit )
    {
        $SQL .= " LIMIT $start_row, $ResultLimit";

        // Get the subset results
        $sql_result = mysql_query($SQL);
    }

    // Get the subset result count
    $result_rows = mysql_num_rows($sql_result);

    // Start XML feed
    $args = array( 'start_row'   => $start_row,
                   'result_rows' => $result_rows,
                   'total_rows'  => $total_rows,
                   'list_type'   => $type );
    $xml .= xml_start_feed( $args );

    // Print 'previous' pueso-directory
    $args = array( 'start_row'  => $start_row,
                   'html_parms' => $_GET );
    $xml .= xml_start_dir( $args );

    // Get XML data for each file in this query.
    switch ( $type )
    {
        case 'vid': $xml .= build_xml_vid( $sql_result, $start_row ); break;
        case 'rec': $xml .= build_xml_rec( $sql_result, $start_row ); break;
    }

    // Print 'next' pueso-directory
    $args = array( 'start_row'   => $start_row,
                   'result_rows' => $result_rows,
                   'total_rows'  => $total_rows,
                   'html_parms'  => $_GET );
    $xml .= xml_end_dir( $args );

    // End XML feed
    $xml .= xml_end_feed();

    // Close mySQL pointer
    mysql_close($db_handle);

    return $xml;
}

//------------------------------------------------------------------------------

function build_query_vid( $sort )
{
    // Start building SQL query
    $SQL = "SELECT * FROM videometadata";

    // Filter file extentions
    $SQL .= " WHERE filename LIKE '%.mp4'";
    $SQL .=    " OR filename LIKE '%.m4v'";
    $SQL .=    " OR filename LIKE '%.mov'";

    // Add sorting
    switch ( $sort )
    {
        case 'title': $SQL .= " ORDER BY title ASC";              break;
        case 'date':  $SQL .= " ORDER BY releasedate, title ASC"; break;
        case 'genre': $SQL .= " ORDER BY category, title ASC";    break;
    }

    return $SQL;
}

function build_query_rec( $sort )
{
    // Start building SQL query
    $SQL = "SELECT * FROM recorded";

    // Filter file extentions
    $SQL .= " WHERE basename LIKE '%.mp4'";
    $SQL .=    " OR basename LIKE '%.m4v'";
    $SQL .=    " OR basename LIKE '%.mov'";

    // Add sorting
    switch ( $sort )
    {
        case 'title':    $SQL .= " ORDER BY title ASC";            break;
        case 'date':     $SQL .= " ORDER BY starttime, title ASC"; break;
        case 'channel':  $SQL .= " ORDER BY chanid, title ASC";    break;
        case 'genre':    $SQL .= " ORDER BY category, title ASC";  break;
        case 'recgroup': $SQL .= " ORDER BY recgroup, title ASC";  break;
    }

    return $SQL;
}

//------------------------------------------------------------------------------

function build_xml_vid( $sql_result, $index )
{
    $xml = '';

    require 'settings.php';

    while ( $db_field = mysql_fetch_assoc($sql_result) )
    {
        $filename  = $db_field['filename'];

        $contentType = "movie";
        $episode     = "";
        if ( 0 < $db_field['season'] )
        {
            $contentType = "episode";
            $episode     = $db_field['season'] . "-" . $db_field['episode'];
        }

        $title      = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] ));
        $subtitle   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['subtitle'] ));
        $synopsis   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['plot'] ));

        $hdimg  = implode("/", array_map("rawurlencode", explode("/", $db_field['coverfile'])));
        $sdimg  = $hdimg;

        $quality = $RokuDisplayType;
        $isHD    = 'false';
        if ( 'HD' == $quality ) { $isHD = 'true'; }

        $url = "$mythtvdata/video/" . implode("/", array_map("rawurlencode", explode("/", $filename)));

        $genrenum = mysql_fetch_assoc(mysql_query("SELECT idgenre FROM videometadatagenre where idvideo='" . $db_field['intid'] . "' "));
        if ($genrenum['idgenre'] == 0) { $genrenum['idgenre'] = 22; }
        $genres = mysql_fetch_assoc(mysql_query("SELECT genre FROM videogenre where intid='" . $genrenum['idgenre'] . "' "));
        $genre = $genres['genre'];

        $args = array(
                'contentType' => $contentType,
                'title'       => $title,
                'subtitle'    => $subtitle,
                'synopsis'    => $synopsis,
                'hdImg'       => "$MythRokuDir/image.php?image=$hdimg",
                'sdImg'       => "$MythRokuDir/image.php?image=$sdimg",
                'streamBitrate'   => 0,
                'streamUrl'       => $url,
                'streamQuality'   => $quality,
                'streamContentId' => $filename,
                'streamFormat'    => pathinfo($filename, PATHINFO_EXTENSION),
                'isHD'        => $isHD,
                'episode'     => $episode,
                'genres'      => $genre,
                'runtime'     => $db_field['length'] * 60,
                'date'        => date("m/d/Y", convert_date($db_field['releasedate'])),
                'starRating'  => $db_field['userrating'] * 10,
                'rating'      => $db_field['rating'],
                'index'       => $index,
                'isRecording' => 'false',
                'delCmd'      => '' );

        $xml .= xml_file( $args );

        $index++;
    }

    return $xml;
}

function build_xml_rec( $sql_result, $index )
{
    $xml = '';

    require 'settings.php';

    while ( $db_field = mysql_fetch_assoc($sql_result) )
    {
        $filename  = $db_field['basename'];
        $programid = $db_field['programid'];

        $SQL = "SELECT * FROM recordedprogram WHERE programid='$programid'";
        $tmp_result   = mysql_query($SQL);
        $tmp_db_field = mysql_fetch_assoc($tmp_result);

        $contentType = "movie";
        $episode     = "";
        if ( 'series' == $tmp_db_field['category_type'] )
        {
            $contentType = "episode";
            $episode     = $tmp_db_field['syndicatedepisodenumber'];
        }

        $title      = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['title'] ));
        $subtitle   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['subtitle'] ));
        $synopsis   = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['description'] ));

        $img    = $db_field['hostname'] . "/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']);
        $hdimg  = "$img/100/56/-1/$filename.100x56x-1.png";
        $sdimg  = "$img/100/75/-1/$filename.100x75x-1.png";

        $quality = $RokuDisplayType;
        $isHD    = 'false';
#       if ( '0' !== $tmp_db_field['hdtv'] ) { $quality = 'HD'; }
        if ( 'HD' == $quality ) { $isHD = 'true'; }

        $url = "$WebServer/pl/stream/" . $db_field['chanid'] . "/" . convert_datetime($db_field['starttime']);

        $genre = htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $db_field['category'] ));

        $args = array(
                'contentType' => $contentType,
                'title'       => $title,
                'subtitle'    => $subtitle,
                'synopsis'    => $synopsis,
                'hdImg'       => "$WebServer/tv/get_pixmap/$hdimg",
                'sdImg'       => "$WebServer/tv/get_pixmap/$sdimg",
                'streamBitrate'   => 0,
                'streamUrl'       => $url,
                'streamQuality'   => $quality,
                'streamContentId' => $filename,
                'streamFormat'    => pathinfo($filename, PATHINFO_EXTENSION),
                'isHD'        => $isHD,
                'episode'     => $episode,
                'genres'      => $genre,
                'runtime'     => convert_datetime($db_field['endtime']) - convert_datetime($db_field['starttime']),
                'date'        => date("m/d/Y h:ia", convert_datetime($db_field['starttime'])),
                'starRating'  => 0,
                'rating'      => '',
                'index'       => $index,
                'isRecording' => 'true',
                'delCmd'      => "$MythRokuDir/mythtv_tv_del.php?basename=$filename" );

        $xml .= xml_file( $args );

        $index++;
    }

    return $xml;
}

//------------------------------------------------------------------------------

function convert_date( $date )
{
    list($year, $month, $day) = explode('-', $date);

    $timestamp = mktime(0, 0, 0, $month, $day, $year);

    return $timestamp;
}

function convert_datetime( $datetime )
{
    list($date, $time) = explode(' ', $datetime);
    list($year, $month, $day) = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time);

    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

    return $timestamp;
}

?>
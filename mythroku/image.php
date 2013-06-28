<?php

require_once 'globals.php';
require_once 'settings.php';
include      'resizeimage.php';

// Required inputs:
//      group -> The storage group name in which the image exists.
//      file  -> File name of the image.
//   or,
//      get_pixmap -> The get_pixmap path for recording images. Example:
//                      <server>/<chanid>/<starttime>/<filename>.png
// Optional inputs:
//      width  -> desired width
//      height -> desired height
//  Note that if either width or heigth is given, but not both, the image will
//  be scaled appropriately.

if ( isset($_GET['get_pixmap']) or
     (isset($_GET['group']) and isset($_GET['file'])) )
{
    $path = "";

    if ( isset($_GET['get_pixmap']) )
    {
        $path = "$WebServer/tv/get_pixmap/" . $_GET['get_pixmap'];
    }
    else
    {
        $path  = $GLOBALS['g_storageGroups'][$_GET['group']];
        $path .= '/' . rawurldecode( $_GET['file'] );
    }

    $image = new SimpleImage();

    $rc = $image->load( $path );
    if ( $rc )
    {
        if ( isset($_GET['height']) and isset($_GET['width']) )
        {
            $image->resize( $_GET['width'], $_GET['height'] );
        }
        else if ( isset($_GET['height']) )
        {
            $image->resizeToHeight( $_GET['height'] );
        }
        else if ( isset($_GET['width']) )
        {
            $image->resizeToWidth( $_GET['width'] );
        }
        else
        {
            $image->resizeToWidth( 219 ); // Average number based on values in
                                          // the ifPosterScreen of the SDK
        }

        $image->output();
    }
}
else
{
    die( "Invalid parameters given" );
}

?>

MythRoku 2010 Peter Nevill
Unless otherwise stated (the @license tag at the top of the file), all files are
distributed under the GNU General Public License.

MythRoku is for streaming MythTV recordings and videos via Roku player.

Prerequisites:
 - Functioning MythTV backend and MythWeb with functional streaming of
   recordings.
 - All recordings and videos stored in H.264 format (.mp4, .m4v, .mov).
 - Web user (e.g. apache) permissions to delete recordings.
 - See 'Tools' section below for some optional prerequisites.

For more information see the Roku developer site:
 - http://www.roku.com/developer

--------------------------------------------------------------------------------
Setup
--------------------------------------------------------------------------------

 1) Set your Roku to development mode

    With your Roku remote, enter the following:
        Home 3x, Up 2x, Right, Left, Right, Left, Right

 2) Modify MythWeb to enable streaming of H.264 files

    Modify /usr/share/mythtv/mythweb/includes/utils.php by adding the following
    around line 247:

        case 'mp4' : return "$url.mp4";

    Modify /usr/share/mythtv/mythweb/modules/stream/stream_raw.pl by adding an
    additonal elsif in the file type section:

        elsif ($basename =~ /\.mp4$/)
        {
            $type = 'video/mp4';
            $suffix = '.mp4';
        }

    Repeat instructions above for .m4v and .mov as well.

 3) Set up the mythroku directory

    You can simply copy the mythroku directory to your mythweb directory
    (typically /usr/share/mythtv/mythweb), but it is easier to just create a
    symbolic link. Regardless, make sure this directory has the same permissions
    as your webserver.

    Ensure that the .htaccess file is in the <pathto>/mythweb/mythroku/
    directory. This simply has:

        RewriteEngine off

    This will stop mythweb from adding its templates to the XML data.

    If you are using authentication to protect MythWeb (best practice), you need
    to add the following to your mythweb.conf file (near the top):

        <LocationMatch .*/mythroku*>
            Allow from 192.168.1.0
        </LocationMatch>

    Edit the settings.php file with your local parameters (i.e. webserver URL
    and MySQL credentials.

    NOTE: In order to be able to delete recordings from your Roku, the MythtWeb
          user (typically 'www-data' or 'apache') will need to have write
          permissions to your recordings. The recordings are typically owned by
          the 'mythtv' user and have group write permissions so it is easiest to
          just add the MythWeb user to the 'mythtv' group. For example:

              usermod -a -G mythtv www-data

          Note that a reboot is required for this to take affect.

 4) Convert all video files to MPEG-4 (H.264) format

    The Roku can only stream MPEG-4 video files (.mp4, .m4v, .mov) and
    recordings generally are stored as MPEG-2 video files.

    Create a user job in mythtv (mythbackend setup-> general-> Job Queue) and
    add the following to a job command:

        <pathtomythrokuplayer>/tools/rokuencode.sh "%DIR%" "%FILE%"

    In your mythconverge -> setting set the AutoRunUserJob1 (or whichever job
    you set it to) data = 1. This will make sure the job is run after every
    recording.

    rokuencode.sh can also be used in the command line to convert existing
    MPEG-2 files.

 5) Install MythRokuPlayer to your Roku

    Make sure you set the ROKU_DEV_TARGET environment variable to your Roku's IP
    address. See the details in <pathtomythrokuplayer>/Makefile to see how this
    is done.

    Once set, simply type the following command in <pathtomythrokuplayer>/:

        $ make install

    When you first open the newly installed MythRokuPlayer channel, you will
    need to set the path to the mythroku directory on your webserver. For
    example:

        http//192.168.1.10/mythweb/mythroku

--------------------------------------------------------------------------------
Additional Notes
--------------------------------------------------------------------------------

1) It has been reported in some cases that jpeg images are not being displayed.
   The issue is in using imagecreatefromjpeg() when GD is not enabled or
   installed. It can be installed as follows (add where appropriate):

    Ubuntu 12.04 (stable):
        sudo apt-get install php5-gd
        sudo service apache2 restart

2) Users may need to comment out the following line in /etc/my.cnf to allow
   access to MySQL:

    bind-address = 127.0.0.1

--------------------------------------------------------------------------------
Debugging and troubleshooting
--------------------------------------------------------------------------------

To access the MythRoku debug console, execute the following:

    telnet <roku_ip_address> 8085

For example:

    telnet $ROKU_DEV_TARGET 8085 or telnet 192.168.1.8 8085

You can use mythtv_test.php to check your setup. It draws from the same data
that is used to create the XML files for the Roku. If this does not work, then
MythRoku will not. Note that You may need to install php5-xsl to get the
mythtv_test.php script to output properly. It can be installed as follows (add
where appropriate):

    Ubuntu 12.04 (stable):
        sudo apt-get install php5-xsl
        sudo service apache2 restart

    Fedora:
        yum install php-xml

--------------------------------------------------------------------------------
Additional Tools
--------------------------------------------------------------------------------

There are a few tools in <pathtomythrokuplayer>/tools that may help you along
the way:

mythrokumake:
    A simple wrapper script for make that does two things:
        - Avoids the need to add an entry in your ~/.bashrc file for the
          ROKU_DEV_TARGET environment variable.
        - Allows you to specify multiple Roku targets if needed.

rokuencode.sh:
    Converts MPEG-2 files to MPEG-4 (H.264) using HandBradeCLI.

    Prerequisites:
        - HandBrakeCLI: To convert recordings into H.264 format.


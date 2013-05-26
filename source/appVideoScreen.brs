'**********************************************************
'**  Video Player Example Application - Video Playback 
'**  November 2009
'**  Copyright (c) 2009 Roku Inc. All Rights Reserved.
'**********************************************************

'***********************************************************
'** Create and show the video screen.  The video screen is
'** a special full screen video playback component.  It
'** handles most of the keypresses automatically and our
'** job is primarily to make sure it has the correct data
'** at startup. We will receive event back on progress and
'** error conditions so it's important to monitor these to
'** understand what's going on, especially in the case of errors
'***********************************************************
Function showVideoScreen(episode as object, prevScreen as object)

    if type(episode) <> "roAssociativeArray" then
        print "invalid data passed to showVideoScreen"
        return -1
    endif

    port = CreateObject("roMessagePort")
    screen = CreateObject("roVideoScreen")
    screen.SetMessagePort(port)

    screen.Show()
    screen.SetPositionNotificationPeriod(30)
    screen.SetContent(episode)
    screen.Show()

    'Uncomment this line to dump the contents of the episode to be played
    'PrintAA(episode)

    while true
        msg = wait(0, port)

        if type(msg) = "roVideoScreenEvent" then
            print "showVideoScreen | msg = "; msg.getMessage() " | index = "; msg.GetIndex()
            if msg.isScreenClosed()
                print "Screen closed"
                exit while
            else if msg.isRequestFailed()
                print "Video request failure: "; msg.GetIndex(); " " msg.GetData() 
                ShowErrorDialog( msg.getMessage(), "MythRoku: Request failed" )
            else if msg.isStatusMessage()
                print "Video status: "; msg.GetIndex(); " " msg.GetData()
            else if msg.isButtonPressed()
                print "Button pressed: "; msg.GetIndex(); " " msg.GetData()
            else if msg.isPlaybackPosition() then

                nowpos = msg.GetIndex()

                http = NewHttp( RegRead("MythRokuServerURL") + "/bookmark.php" )
                http.AddParam( "act", "set" )
                if episode.Recording then
                    http.AddParam( "type",   "rec"             )
                    http.AddParam( "chanid", episode.chanid    )
                    http.AddParam( "start",  episode.starttime )
                else
                    http.AddParam( "type", "vid"              )
                    http.AddParam( "intid", episode.ContentId )
                end if
                http.AddParam( "sec", nowpos.toStr() )

                http.GetToStringWithRetry()

            else
                print "Unexpected event type: "; msg.GetType()
            end if
        else
            print "Unexpected message class: "; type(msg)
        end if
    end while

    'The bookmark position may have changed, so refresh the details screen.
    refreshDetailScreen( prevScreen, episode )

End Function


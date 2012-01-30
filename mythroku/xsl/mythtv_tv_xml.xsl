<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html"/> 

    <xsl:template match="/">
        <html>
            <head>
                <title>MythRoku Test Page</title>
            </head>
            <body bgcolor="white" text="blue">
		<br />
		<h1>MythRoku Test Page: Recordings</h1>
		<br />
		Use this page to help diagnose any problems there may be with settings and configuration. If all data displays ok here then it should work with Roku!
		<br /><br />
		<xsl:apply-templates select="feed" />
            </body>
        </html>
    </xsl:template>

    <xsl:template match="feed">
	<xsl:for-each select="item">
	    <xsl:variable name="url"    select="media/streamUrl" />
	    <xsl:variable name="delcmd" select="delcommand" />

	    <b><xsl:text>[</xsl:text>
	    <xsl:value-of select="index" /><xsl:text> of </xsl:text>
	    <xsl:value-of select="../resultLength" /><xsl:text>] </xsl:text>
	    <xsl:value-of select="title" /><xsl:text> </xsl:text></b>
	    <i><xsl:value-of select="subtitle" /></i><br/>

	    <table border="1">
		<tr>
		    <td rowspan="5">
			<xsl:variable name="img" select="hdImg" />
			<a href="{$url}"><img src="{$img}" width="300" height="168" /></a>
		    </td>
		    <td><b>Type:</b></td><td><xsl:value-of select="contentType" /></td>
		    <td><b>Genre:</b></td><td><xsl:value-of select="genres" /></td>
		    <td><b>Quality:</b></td><td><xsl:value-of select="media/streamQuality" /></td>
		    <td><b>Rating:</b></td><td><xsl:value-of select="rating" /></td>
		</tr>
		<tr>
		    <td><b>Runtime:</b></td><td><xsl:value-of select="runtime div 60" /> min</td>
		    <td><b>Date:</b></td><td><xsl:value-of select="date" /></td>
		    <td><b>Format:</b></td><td><xsl:value-of select="media/streamFormat" /></td>
		    <td><b>Star Rating:</b></td><td><xsl:value-of select="starrating" /></td>
		</tr>
		<tr>
		    <td><b>Episode:</b></td><td><xsl:value-of select="episode" /></td>
		</tr>
		<tr><td colspan="8"><b>Stream Url:</b><br/><a href="{$url}"><xsl:copy-of select="$url" /></a></td></tr>
		<tr><td colspan="8"><b>Synopsis:</b><br/><xsl:value-of select="synopsis" /></td></tr>
		<tr><td colspan="9"><b>Delete Command:</b><br/><a href="{$delcmd}"><xsl:copy-of select="$delcmd" /></a></td></tr>
	    </table>

	    <br /> 

	</xsl:for-each>
    </xsl:template>

</xsl:stylesheet>

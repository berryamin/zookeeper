<?php
/**
 * Zookeeper Online
 *
 * @author Jim Mason <jmason@ibinx.com>
 * @copyright Copyright (C) 1997-2018 Jim Mason <jmason@ibinx.com>
 * @link https://zookeeper.ibinx.com/
 * @license GPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License,
 * version 3, along with this program.  If not, see
 * http://www.gnu.org/licenses/
 *
 */

namespace ZK\UI;

use ZK\Engine\Engine;
use ZK\Engine\ILibrary;

use ZK\UI\UICommon as UI;

class Search extends MenuItem {
    const GENRES = [
        "B"=>"Blues",
        "C"=>"Country",
        "G"=>"General",
        "H"=>"Hip-hop",
        "J"=>"Jazz",
        "K"=>"Childrens",
        "L"=>"Classical",
        "N"=>"Novelty",
        "O"=>"Comedy",
        "P"=>"Spoken Word",
        "R"=>"Reggae",
        "S"=>"Soundtrack",
        "W"=>"World",
    ];
    
    const MEDIA = [
        "C"=>"CD",
        "M"=>"Cassette",
        "S"=>"7\"",
        "T"=>"10\"",
        "V"=>"12\"",
    ];
    
    const LENGTHS = [
        "E"=>"EP",
        "F"=>"Full",
        "S"=>"Single",
    ];
    
    const LOCATIONS = [
        "D"=>"Received",
        "E"=>"Review Shelf",
        "F"=>"Out for Review",
        "H"=>"Pending Appr",
        "C"=>"A-File",
        "G"=>"Storage",
        "L"=>"Library",
        "M"=>"Missing",
        "R"=>"Needs Repair",
        "U"=>"Deaccessioned",
    ];
    
    private static $actions = [
        [ "find", "ftSearch" ],
        [ "findAlbum", "searchByAlbumKey" ],
        [ "search", "doSearch" ],
    ];

    private $maxresults = 10;

    private $noTables = false;

    private $sortBy;
    
    public function processLocal($action, $subaction) {
        return $this->dispatchAction($action, self::$actions);
    }

    public function ftSearch() {
?>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript" SRC="js/zooscript.js"></SCRIPT>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript" SRC="js/zootext.js"></SCRIPT>
<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript"><!--
// Jim Mason <jmason@ibinx.com>
// Copyright (C) 2005-2018 Jim Mason.  All Rights Reserved.
lists = [ <? if($this->session->isAuth("u")) echo "\"Tags\", "; ?>"Albums", "Compilations", "Labels", "Playlists", "Reviews", "Tracks" ];

function onSearch(sync,e) {
   if(sync.Timer) {
      clearTimeout(sync.Timer);
      sync.Timer = null;
   }
   sync.Timer = setTimeout('onSearchNow()', 500);
}

function onSearchNow() {
   loadXMLDoc("zkapi.php?method=searchRq&size=5&key=" + urlEncode(document.forms[0].search.value) + "&session=<?echo $session;?>");
}

function processReqChange(req) {
  if(req.readyState == 4) {
    // document loaded
    if (req.status == 200) {
      // success!
      var rs = req.responseXML.getElementsByTagName("searchRs")[0];
      var type = rs.getAttribute("type");
      if(type != '') {
        var method = type.substr(0,1).toUpperCase() + type.substr(1).toLowerCase();
        var items = req.responseXML.getElementsByTagName(type.toLowerCase());
        if(items && items[0])
          eval('emit' + method + '(getTable(type), items[0])');
        return;
      }
      clearSavedTable();
      document.getElementById("total").innerHTML = "(" + rs.getAttribute("total") + " total)";
      var results = document.getElementById("results");
      while(results.firstChild)
        results.removeChild(results.firstChild);
      for(var i=0; i<lists.length; i++) {
        var items = req.responseXML.getElementsByTagName(lists[i].toLowerCase());
        if(items && items[0])
          eval('emit' + lists[i] + '(emitTable(results, lists[i]), items[0])');
      }
    } else {
      alert("There was a problem retrieving the XML data:\n" + req.statusText);
    }
  }
}

function setFocus() {
  document.forms[0].search.focus();
  var val = document.forms[0].search.value;
  if(val.length > 0) onSearchNow();
  document.forms[0].search.value = val;  // reset value to force cursor to end
}
// -->
</SCRIPT>
<?
        echo "<FORM ACTION=\"?\" METHOD=\"POST\">\n";
        echo "<P><B>Find It:</B>&nbsp;&nbsp;<INPUT TYPE=TEXT CLASS=text STYLE=\"width:214px;\" NAME=search VALUE=\"".$_REQUEST['search']."\" autocomplete=off onkeyup=\"onSearch(document.forms[0],event);\" onkeypress=\"return event.keyCode != 13;\">&nbsp;&nbsp;<SPAN ID=\"total\"></SPAN></P>\n";
        echo "<INPUT TYPE=HIDDEN NAME=action VALUE=\"find\">\n";
        echo "<INPUT TYPE=HIDDEN NAME=session VALUE=\"$session\">\n";
        echo "</FORM>\n";
        echo "<SPAN ID=\"results\">Search the database for music, reviews, and playlists.";
        echo "</SPAN>\n";
    }

    public function searchByAlbumKey() {
        $n = $_REQUEST["n"];
    
        $albums = Engine::api(ILibrary::class)->search(ILibrary::ALBUM_KEY, 0, 1, $n);
    
        $artist = strcmp(substr($albums[0]["artist"], 0, 8), "[coll]: ")?
                      $albums[0]["artist"]:"Various Artists";
        echo "<TABLE WIDTH=\"100%\">\n  <TR><TH ALIGN=LEFT COLSPAN=5 CLASS=\"secdiv\">" .
                  UI::HTMLify($artist, 20) . " / " .
                  UI::HTMLify($albums[0]["album"], 20);
        if($this->session->isAuth("u"))
            echo "&nbsp;&nbsp;(Tag #".$albums[0]["tag"].")";
        echo "</TH></TR>\n</TABLE>";
        echo "<TABLE>\n";
        echo "  <TR><TD ALIGN=RIGHT>Album:</TD><TD><B>";
    
        echo "<A HREF=\"".
                     "?s=byAlbum&amp;n=". UI::URLify($albums[0]["album"]).
                     "&amp;q=". $this->maxresults.
                     "&amp;action=search&amp;session=$session".
                     "\" CLASS=\"nav\">";
        echo htmlentities($albums[0]["album"]) . "</A></B></TD>";
    
        $medium = " " . Search::MEDIA[$albums[0]["medium"]];
        if($medium == " CD") $medium = "";
    
        $showMissing = "missing";
        $missingSelect = "entry_0=Missing+:(";
        echo "<TD WIDTH=80>&nbsp;</TD>" .
             "<TD ALIGN=RIGHT>Collection:</TD><TD><B>";
        switch($albums[0]["location"]) {
        case 'G':
            echo "<I>Deep&nbsp;Storage&nbsp;".$albums[0]["bin"]."</I>";
            $showMissing = 0;
            break;
        case 'M':
            echo "<I>Missing</I>";
            $showMissing = "found";
            $missingSelect = "entry_0=Found!++:)";
            break;
        case 'E':
            echo "<I>Review Shelf</I>";
            break;
        case 'F':
            echo "<I>Out for Review</I>";
            break;
        case 'U':
            echo "<I>Deaccessioned</I>";
            $showMissing = 0;
            break;
        default:
            echo Search::GENRES[$albums[0]["category"]] . $medium;
            break;
        }
        echo "</B>";
        if($this->session->isAuth("u") && $showMissing) {
            $urls = Engine::param('urls');
            if(array_key_exists('report_missing', $urls)) {
                $user = Engine::api(ILibrary::class)->search(ILibrary::PASSWD_NAME, 0, 1, $this->session->getUser());
                $url = str_replace('%USERNAME%', UI::URLify($user[0]["realname"]), $urls['report_missing']);
                $url = str_replace('%ALBUMTAG%', $albums[0]["tag"], $url);
                echo "&nbsp;&nbsp;<A HREF=\"$url\" CLASS=\"nav\" TARGET=\"_blank\">[report $showMissing...]</A>";
            }
        }
        echo "</TD></TR>\n";
        echo "  <TR><TD ALIGN=RIGHT>Artist:</TD><TD><B>";
    
        if(strcmp($artist, "Various Artists")) {
            echo "<A HREF=\"".
                         "?s=byArtist&amp;n=". UI::URLify($artist).
                         "&amp;q=". $this->maxresults.
                         "&amp;action=search&amp;session=$session".
                         "\" CLASS=\"nav\">";
            echo htmlentities($artist) . "</A></B></TD>";
        } else
            echo htmlentities($artist) . "</B></TD>";
        echo "<TD>&nbsp;</TD>" .
             "<TD ALIGN=RIGHT>Added:</TD><TD><B>";
        list ($year, $month, $day) = explode("-", $albums[0]["created"]);
    
        echo "$month/$year</B></TD></TR>\n";
        echo "  <TR><TD ALIGN=RIGHT>Label:</TD><TD><B>";
        if($albums[0]["pubkey"] != 0) {
            $label = Engine::api(ILibrary::class)->search(ILibrary::LABEL_PUBKEY, 0, 1, $albums[0]["pubkey"]);
            if(sizeof($label)) {
                echo "<A HREF=\"".
                               "?s=byLabelKey&amp;n=". UI::URLify($albums[0]["pubkey"]).
                               "&amp;q=". $this->maxresults.
                               "&amp;action=search&amp;session=$session".
                               "\" CLASS=\"nav\">";
                echo htmlentities($label[0]["name"]) . "</A>";
            } else
                echo "(Unknown)";
        } else
            echo "(Unknown)";
        echo "</B></TD><TD COLSPAN=2>&nbsp;</TD><TD>";
        $this->newEntity(Reviews::class)->emitReviewHook();
        echo "</TD></TR>\n";
    
        echo "</TABLE>\n<BR>\n";
    
        // Emit Currents data
        $this->newEntity(AddManager::class)->viewCurrents($n);
    
        // Emit last plays
        $this->newEntity(Playlists::class)->viewLastPlays($n, 6);
    
        // Emit Review
        $this->newEntity(Reviews::class)->viewReview2();
    
        // Emit Tracks
        echo "<TABLE WIDTH=\"100%\">\n  <TR><TH COLSPAN=5 ALIGN=LEFT CLASS=\"secdiv\">Track Listing</TH></TR></TABLE>\n";
    
        // Handle collection tracks
        $albums = Engine::api(ILibrary::class)->search(ILibrary::COLL_KEY, 0, 200, $n);
        for($i = 0; $i < sizeof($albums); $i++) {
            if($i == 0) {
                if($this->noTables)
                    // 3 20 32
                    echo "<PRE><B>  # Artist               Track Name                      </B>\n";
                else
                    echo "<TABLE>\n  <TR><TH>&nbsp;</TH><TH ALIGN=LEFT>Artist</TH><TH ALIGN=LEFT>Track Name</TH></TR>\n";
            }
    
            // Number
            if($this->noTables)
                echo UI::HTMLifyNum($albums[$i]["seq"], 3);
            echo "  <TR><TD ALIGN=RIGHT>".$albums[$i]["seq"].".</TD><TD>";
    
            // Artist Name
            echo "<A HREF=\"".
                               "?s=byArtist&amp;n=". UI::URLify($albums[$i]["artist"]).
                               "&amp;q=". $this->maxresults.
                               "&amp;action=search&amp;session=$session".
                               "\">";
            echo UI::HTMLify($albums[$i]["artist"], 20), "</A>";
            if(!$this->noTables)
                echo "</TD><TD>\n";
    
            // Track Name
            echo "<A HREF=\"".
                               "?s=byTrack&amp;n=". UI::URLify($albums[$i]["track"]).
                               "&amp;q=". $this->maxresults.
                               "&amp;action=search&amp;session=$session".
                               "\">";
            echo UI::HTMLify($albums[$i]["track"], 32). "</A>";
            if(!$this->noTables)
                echo "</TD></TR>";
            echo "\n";
        }
        if($i)
            echo $this->closeList();
        else {
            // Handle non-collection tracks
            $tracks = Engine::api(ILibrary::class)->search(ILibrary::TRACK_KEY, 0, 200, $n);
    
            $mid = sizeof($tracks) / 2;
            for($i = 0; $i < $mid; $i++){
                if(!$opened) {
                    if($this->noTables)
                        // 3 32
                        echo "<PRE><B>  # Track Name                      </B>\n";
                    else
                        echo "<TABLE>\n";
    
                    $opened = 1;
                }
                // Number
    
                if($mid - $i < 1)
                    if($this->noTables)
                        echo UI::HTMLify(" ", 36);
                    else
                        echo "  <TR><TD COLSPAN=3>&nbsp;</TD>";
                else {
                    if($this->noTables)
                        echo UI::HTMLifyNum($tracks[$i]["seq"], 3);
                    else
                        echo "  <TR><TD ALIGN=RIGHT>".$tracks[$i]["seq"].".</TD><TD>";
                    // Name
                    echo "<A HREF=\"".
                                 "?s=byTrack&amp;n=". UI::URLify($tracks[$i]["track"]).
                                 "&amp;q=". $this->maxresults.
                                 "&amp;action=search&amp;session=$session".
                                 "\">";
                    echo UI::HTMLify($tracks[$i]["track"], 32), "</A>";
                    if(!$this->noTables)
                        echo "</TD><TD>&nbsp;</TD>";
                }
    
                if($this->noTables)
                    echo UI::HTMLifyNum($tracks[$mid + $i]["seq"], 3);
                else
                    echo "<TD ALIGN=RIGHT>".$tracks[$mid + $i]["seq"].".</TD><TD>";
                // Name
                echo "<A HREF=\"".
                                    "?s=byTrack&amp;n=". UI::URLify($tracks[$mid + $i]["track"]).
                                    "&amp;q=". $this->maxresults.
                                    "&amp;action=search&amp;session=$session".
                                    "\">";
                echo UI::HTMLify($tracks[$mid + $i]["track"], 32), "</A>";
    
                if(!$this->noTables)
                    echo "</TD></TR>";
                echo "\n";
    
            }
            if($opened) echo $this->closeList();
        }
    
        UI::setFocus();
    }
    
    public function doSearch() {
        $this->checkBrowserCaps();
    
        $n = stripslashes($_REQUEST["n"]);
    
        $p = $_REQUEST["p"];
        settype($p, "integer");
        $q = $_REQUEST["q"];
        if($q)
            $this->maxresults = (integer)$q;
        if(strlen($n))
            $searchType = $_REQUEST["s"];
    
        switch($searchType) {
        case "byAlbum":
            $this->searchByAlbum();
            break;
        case "byAlbumKey":
            $this->searchByAlbumKey();
            break;
        case "byArtist":
            $this->searchByArtist();
            break;
        case "byTrack":
            $this->searchByTrack();
            break;
        case "byLabel":
            $this->searchByLabel();
            break;
        case "byLabelKey":
            $this->searchByLabelKey();
            break;
        case "byCollArtist":
            // deprecated
            break;
        case "byCollTrack":
            $this->searchByCollTrack(-1, $this->maxresults);
            break;
        case "byReviewer":
            $this->searchByReviewer();
            break;
        default:
            $this->searchForm("");
            echo "<P><B>Tip:  For a more extensive search, try ".
                 "<A HREF=\"".
                 "?session=".$this->session->getSessionID()."&amp;action=find\" CLASS=\"nav\">Find It!</A>\n";
            break;
        }
    }
    
    // returns closing tag for output
    private function closeList() {
        if($this->noTables)
        $close = "</PRE>\n";
            else
        $close = "</TABLE>\n";
        return $close;
    }
    
    private function searchString($arg, $exactMatchOnly) {
        if(!$exactMatchOnly)
            $arg .= "*";
        return $arg;
    }
    
    // CheckBrowserCaps
    //
    // Check browser's capabilities:
    //     noTables property set true if browser does not support tables
    //
    private function checkBrowserCaps() {
        // For now, we naively assume all browsers support tables except Lynx.
        $this->noTables = (substr($_SERVER["HTTP_USER_AGENT"], 0, 5) == "Lynx/");
    }
    
    private function searchForm($title, $tag=0) {
        switch($_REQUEST["s"]){
        case "byArtist":
        case "byCollArtist":
            $chkArtist = " checked";
            break;
        case "byAlbum":
            $chkAlbum = " checked";
            break;
        case "byTrack":
        case "byCollTrack":
            $chkTrack = " checked";
            break;
        case "byLabel":
            $chkLabel = " checked";
            break;
        }
        if ($chkArtist || $chkAlbum || $chkTrack || $chkLabel ) {
            if($_REQUEST["m"])
                $chkExact =" checked";
            if($_REQUEST["n"])
                $searchFor =" VALUE=\"".htmlspecialchars($_REQUEST["n"])."\"";
        } else {
            // Default to search by artist
            $chkArtist = " checked";
        }
    
        switch ($this->maxresults) {
        case 15:
            $o_fifteen = " SELECTED";
            break;
        case 20:
            $o_twenty = " SELECTED";
            break;
        case 50:
            $o_fifty = " SELECTED";
            break;
        default:
            $o_ten = " SELECTED";
            break;
        }
    ?>
    <FORM ACTION="?" METHOD=POST>
    <TABLE WIDTH="100%">
      <TR><TD>
        <TABLE CELLPADDING=2>
          <TR>
            <TD ALIGN=RIGHT><B>Search the Library by:</B></TD>
            <TD><INPUT TYPE=RADIO NAME=s VALUE="byArtist"<?echo $chkArtist;?>>Artist</TD>
            <TD><INPUT TYPE=RADIO NAME=s VALUE="byAlbum"<?echo $chkAlbum;?>>Album</TD>
            <TD><INPUT TYPE=RADIO NAME=s VALUE="byTrack"<?echo $chkTrack;?>>Track</TD>
            <TD><INPUT TYPE=RADIO NAME=s VALUE="byLabel"<?echo $chkLabel;?>>Label</TD>
          </TR>
          <TR>
            <TD ALIGN=RIGHT>For:</TD>
            <TD COLSPAN=4><INPUT TYPE=TEXT NAME=n<?echo $searchFor;?> SIZE=40 CLASS=input autocomplete=off></TD>
          </TR>
          <TR>
            <TD ALIGN=RIGHT>Results per page:</TD>
            <TD><SELECT NAME=q>
              <OPTION<?echo $o_ten;?>>10
              <OPTION<?echo $o_fifteen;?>>15
              <OPTION<?echo $o_twenty;?>>20
              <OPTION<?echo $o_fifty;?>>50</SELECT></TD>
            <TD COLSPAN=2><INPUT TYPE=CHECKBOX NAME=m VALUE=1<?echo $chkExact;?>>Exact match only</TD>
            <TD ALIGN=RIGHT><INPUT TYPE=SUBMIT VALUE="Search"></TD>
          </TR>
        </TABLE>
      </TD></TR>
    </TABLE>
    <INPUT TYPE=HIDDEN NAME=session VALUE="<?echo $this->session->getSessionID();?>">
    <INPUT TYPE=HIDDEN NAME=action VALUE="search">
    </FORM>
    <?
        if($title)
            echo "<BR>\n<TABLE WIDTH=\"100%\"><TR><TH ALIGN=LEFT CLASS=\"subhead\">$title</TH></TR></TABLE>\n";
    
        UI::setFocus("n");
    }
    
    private function outputAlbums($searchType, $searchString, $albums, $p) {
        $m = $_REQUEST["m"];
        $n = $_REQUEST["n"];
    
        Engine::api(ILibrary::class)->markAlbumsReviewed($albums, $this->session->isAuth("u"));
    
        for($i = 0; $i < sizeof($albums); $i++){
            if (! $opened ) {
                if($this->noTables)
                    echo "<PRE><B>Artist               Album                Coll    Medium  Label               </B>\n";
                else
                    echo "<TABLE CELLPADDING=2>\n  <TR><TH>Artist</TH><TH></TH><TH>Album</TH><TH>Collection</TH><TH COLSPAN=2>Medium</TH><TH>Add Date</TH><TH>Label</TH></TR>\n";
                $opened = 1;
            }
            $count++;
    
            // Artist
            if (!$this->noTables) {
                echo "  <TR><TD>";
                if(!$albums[$i]["artist"])
                    echo "&nbsp;";
            }
            if (preg_match("/^\[coll\]/i", $albums[$i]["artist"])) {
                // It's a collection; HREF the album key
                echo "<A HREF=\"".
                                "?s=byAlbumKey&amp;n=". UI::URLify($albums[$i]["tag"]).
                                "&amp;q=". $this->maxresults.
                                "&amp;action=search&amp;session=".$this->session->getSessionID().
                                "\">";
            } else {
                echo "<A HREF=\"".
                                "?s=byArtist&amp;n=". UI::URLify($albums[$i]["artist"]).
                                "&amp;q=". $this->maxresults.
                                "&amp;action=search&amp;session=".$this->session->getSessionID().
                                "\">";
            }
            echo UI::HTMLify($albums[$i]["artist"], 20) . "</A>";
            // Album
            if(!$this->noTables) {
                echo "</TD><TD>";
                if($albums[$i]["REVIEWED"])
                        echo "<A HREF=\"".
                             "?s=byAlbumKey&amp;n=". UI::URLify($albums[$i]["tag"]).
                             "&amp;q=". $this->maxresults.
                             "&amp;action=search&amp;session=".$this->session->getSessionID().
                             "\"><IMG SRC=\"img/rinfo_beta.gif\" " .
                             "ALT=\"Album Review\" " .
                             "WIDTH=12 HEIGHT=11 BORDER=0></A></TD><TD>";
                else
                    echo "</TD><TD>";
            }
            echo "<A HREF=\"".
                               "?s=byAlbumKey&amp;n=". UI::URLify($albums[$i]["tag"]).
                               "&amp;q=". $this->maxresults.
                               "&amp;action=search&amp;session=".$this->session->getSessionID().
                               "\">";
            echo UI::HTMLify($albums[$i]["album"], 20) . "</A>";
            if(!$this->noTables)
                echo "</TD><TD>";
            // Genre
            switch($albums[$i]["location"]) {
            case 'G':
                echo "<I>Deep&nbsp;Storage&nbsp;".$albums[$i]["bin"]."</I>";
                break;
            case 'M':
                echo "<I>Missing</I>";
                break;
            case 'E':
                echo "<I>Review Shelf</I>";
                break;
            case 'F':
                echo "<I>Out for Review</I>";
                break;
            case 'U':
                echo "<I>Deaccessioned</I>";
                break;
            default:
                echo UI::HTMLify(Search::GENRES[$albums[$i]["category"]], 7);
                break;
            }
            if(!$this->noTables)
                echo "</TD><TD>";
            // Medium & Length
            echo UI::HTMLify(Search::MEDIA[$albums[$i]["medium"]], 3);
            if(!$this->noTables)
                echo "</TD><TD>";
            echo UI::HTMLify(Search::LENGTHS[$albums[$i]["size"]], 3);
            if(!$this->noTables)
                echo "</TD><TD ALIGN=CENTER>";
            // Add Date
            list($year, $month, $day) = explode("-", $albums[$i]["created"]);
            if(!$this->noTables)
                echo $month. "/". substr($year, 2, 2). "</TD><TD>";
            // Label
            if ($albums[$i]["pubkey"] != 0) {
                $labelKey = $albums[$i]["pubkey"];
                echo "<A HREF=\"".
                                   "?s=byLabelKey&amp;n=". UI::URLify($labelKey).
                                   "&amp;q=". $this->maxresults.
                                   "&amp;action=search&amp;session=".$this->session->getSessionID().
                                   "\">";
    
                if(!$labelCache[$labelKey]) {
                    // Secondary search for label name
                    $labels = Engine::api(ILibrary::class)->search(ILibrary::LABEL_PUBKEY, 0, 1, $labelKey);
                    if(sizeof($labels))
                        $labelCache[$labelKey] = $labels[0]["name"];
                    else
                        $labelCache[$labelKey] = "(Unknown)";
                }
    
                echo UI::HTMLify($labelCache[$labelKey], 20). "</A>";
                if($this->noTables)
                    echo "\n";
                else
                    echo "</TD></TR>\n";
            } else {
                echo UI::HTMLify("Unknown", 20);
                if(!$this->noTables)
                    echo "</TD></TR>";
                echo "\n";
            }
        }
        if($opened && $p>0) {
            echo $this->closeList();
            if(substr($searchString, -1) != "*")
                $m = "&amp;m=1";
    
            echo "<P><A HREF=\"".
                                  "?s=$searchType&amp;n=". UI::URLify($n).
                                  "&amp;p=". UI::URLify($p). $m.
                                  "&amp;q=". $this->maxresults.
                                  "&amp;action=search&amp;session=".$this->session->getSessionID().
                                  "\">[Next $this->maxresults albums &gt;&gt;]</A>\n";
            $closed = 1;
        }

        if(!$closed && ($searchType == "byArtist")) {
            $p = 0;
            $this->searchByCollArtist($opened, $maxresults - $count);
        } else {
            if ($opened) {
                if(!$closed)
                    echo $this->closeList();
            } else {
                echo "<H3>No albums found</H3>\n";
                if($m)
                    echo "Hint: Uncheck \"Exact match only\" box to broaden search.";
            }
        }
    }
    
    private function searchByAlbum() {
        $this->searchForm("Album Search Results");
        $p = $_REQUEST["p"];
        if($p == "") $p = 0;
    
        $search = $this->searchString($_REQUEST["n"], $_REQUEST["m"]);
        $albums = Engine::api(ILibrary::class)->searchPos(ILibrary::ALBUM_NAME, $p, $this->maxresults, $search);
        $this->outputAlbums("byAlbum", $search, $albums, $p);
    }        
    
    private function searchByArtist() {
        $this->searchForm("Artist Search Results");
        $p = $_REQUEST["p"];
        if($p == "") $p = 0;
        $search = $this->searchString($_REQUEST["n"],$_REQUEST["m"]);
        $albums = Engine::api(ILibrary::class)->searchPos(ILibrary::ALBUM_ARTIST, $p, $this->maxresults, $search);
        $this->outputAlbums("byArtist", $search, $albums, $p);
    }
    
    private function reviewerColHeader($header, $static) {
        $command = $header;
        if(!strcmp($header, $this->sortBy)) {
            $command .= "-";
            $selected = 1;
        } else if(!strcmp($header . "-", $this->sortBy))
            $selected = 2;
    
        if($static)
            echo "  <TH ALIGN=LEFT$width><U>$header</U>";
        else
            echo "  <TH ALIGN=LEFT$width><A CLASS=\"nav\" HREF=\"?s=byReviewer&amp;n=".$_REQUEST["n"]."&amp;p=0&amp;q=15&amp;action=viewDJReviews&amp;session=".$this->session->getSessionID()."&amp;sortBy=$command\">$header</A>";
    
        if($selected && !$static)
            echo "&nbsp;<IMG SRC=\"img/arrow_" . (($selected==1)?"down":"up") . "_beta.gif\" BORDER=0 WIDTH=8 HEIGHT=4 ALIGN=MIDDLE ALT=\"sort\">";
    
        echo "</TH>\n";
    }
    
    private function reviewerAlbums($searchType, $searchString, $albums) {
        for($i = 0; $i < sizeof($albums); $i++){
            if (! $opened ) {
                if($this->noTables)
                    echo "<PRE><B>Artist               Album                Label                Date Reviewed</B>\n";
                else {
                    echo "<TABLE CELLPADDING=2 CELLSPACING=0 BORDER=0>\n";
                    $static = 0;
                    reviewerColHeader("Artist", $static);
                    reviewerColHeader("Album", $static);
                    reviewerColHeader("Label", $static);
                    reviewerColHeader("Date Reviewed", $static);
                }
                $opened = 1;
            }
            $count++;
    
            // Artist
            if (!$this->noTables) {
                echo "  <TR><TD>";
                if(!$albums[$i]["artist"])
                    echo "&nbsp;";
            }
            if (preg_match("/^\[coll\]/i", $albums[$i]["artist"])) {
                // It's a collection; HREF the album key
                echo "<A HREF=\"".
                                "?s=byAlbumKey&amp;n=". UI::URLify($albums[$i]["tag"]).
                                "&amp;q=". $this->maxresults.
                                "&amp;action=search&amp;session=".$this->session->getSessionID().
                                "\">";
            } else {
                echo "<A HREF=\"".
                                "?s=byArtist&amp;n=". UI::URLify($albums[$i]["artist"]).
                                "&amp;q=". $this->maxresults.
                                "&amp;action=search&amp;session=".$this->session->getSessionID().
                                "\">";
            }
            echo UI::HTMLify($albums[$i]["artist"], 20) . "</A>";
    
            // Album
            if(!$this->noTables)
                echo "</TD><TD>";
            echo "<A HREF=\"".
                               "?s=byAlbumKey&amp;n=". UI::URLify($albums[$i]["tag"]).
                               "&amp;q=". $this->maxresults.
                               "&amp;action=search&amp;session=".$this->session->getSessionID().
                               "\">";
            echo UI::HTMLify($albums[$i]["album"], 20) . "</A>";
            if($this->session->isAuth("u"))
                echo " <FONT CLASS=\"sub\">(Tag&nbsp;#". $albums[$i]["tag"] .")</FONT>";
            if(!$this->noTables)
                echo "</TD><TD>";
            // Label
            if ($albums[$i]["pubkey"] != 0) {
                $labelKey = $albums[$i]["pubkey"];
                echo "<A HREF=\"".
                                   "?s=byLabelKey&amp;n=". UI::URLify($labelKey).
                                   "&amp;q=". $this->maxresults.
                                   "&amp;action=search&amp;session=".$this->session->getSessionID().
                                   "\">";
    
                echo UI::HTMLify($albums[$i]["name"], 20). "</A>";
            } else {
                echo UI::HTMLify("Unknown", 20);
            }
    
            if(!$this->noTables)
                echo "</TD><TD>";
            // Review date
            echo substr($albums[$i]["reviewed"], 0, 10);
            if($this->noTables)
                echo "\n";
            else
                echo "</TD></TR>\n";
        }
        $p = $_REQUEST["p"];
        if($opened && $p>0) {
            echo $this->closeList();
            $m = $_REQUEST["m"];
            if(substr($searchString, -1) != "*")
                $m = "&amp;m=1";
    
            echo "<P><A HREF=\"".
                                  "?s=$searchType&amp;n=". UI::URLify($_REQUEST["n"]).
                                  "&amp;p=". UI::URLify($p). $m.
                                  "&amp;q=". $this->maxresults.
                                  "&amp;action=$action&amp;session=".$this->session->getSessionID().
                                  "&amp;sortBy=$this->sortBy".
                                  "\">[Next $this->maxresults albums &gt;&gt;]</A>\n";
            $closed = 1;
        }
    
        if ($opened) {
            if(!$closed)
                echo $this->closeList();
        } else {
            echo "<H3>No albums found</H3>\n";
        }
    }
    
    private function searchByReviewer() {
        if(!$this->sortBy)$this->sortBy="Artist";
    
        if($_REQUEST["n"]) {
            $airnames = Engine::api(IDJ::class)->getAirnames($this->session->getUser(), $_REQUEST["n"]);
            if ($arow = $airnames->fetch())
                $name = $arow["airname"];
        }
    
        if($name) {
            echo "<TABLE WIDTH=\"100%\"><TR><TH ALIGN=LEFT CLASS=\"subhead\">$name's Album Reviews</TH></TR></TABLE>\n";
            $p = $_REQUEST["p"];
            if($p == "") $p = 0;
            $albums = Engine::api(ILibrary::class)->searchPos(ILibrary::ALBUM_AIRNAME, $p, $this->maxresults, $_REQUEST["n"], $this->sortBy);
            reviewerAlbums("byReviewer", $search, $albums);
        }
    }
    
    private function searchByCollArtist($opened, $remaining) {
        if($opened == -1) {
            $this->searchForm("Artist Search Results");
            $opened++;
        }
    
        $p = $_REQUEST["p"];
        if($p == "") $p = 0;

        if($opened && $p>0) {
            echo $this->closeList();
            if(substr($search, -1) != "*")
                $m = "&amp;m=1";
            echo "<P><A HREF=\"".
                                  "?s=byCollArtist&amp;n=". URLify($n).
                                  "&amp;p=". URLify($p). $m.
                                  "&amp;q=". $maxresults.
                                  "&amp;action=search&amp;session=".$this->session->getSessionID().
                                  "\">[Next $this->maxresults albums &gt;&gt;]</A>\n";
            $closed = 1;
        }
    
        if ($opened) {
            if(!$closed)
                echo $this->closeList();
        } else {
            echo "<H3>No albums found</H3>\n";
            if($m)
                echo "Hint: Uncheck \"Exact match only\" box to broaden search.";
        }
    }

    private function searchByCollTrack($opened, $remaining) {
        $libraryAPI = Engine::api(ILibrary::class);
    
        if($opened == -1) {
            $this->searchForm("Track Search Results");
            $opened++;
        }
    
        $p = $_REQUEST["p"];
        if($p == "") $p = 0;

        if($opened && $p>0) {
            echo $this->closeList();
            echo "<P><A HREF=\"".
                                  "?s=byCollTrack&amp;n=". UI::URLify($_REQUEST["n"]), 
                                  "&amp;p=". UI::URLify($p),
                                  "&amp;q=". $this->maxresults.
                                  "&amp;action=search&amp;session=".$this->session->getSessionID().
                                  "\">[Next $this->maxresults albums &gt;&gt;]</A>\n";
            $closed = 1;
        }
    
        if ($opened) {
            if(!$closed)
                echo $this->closeList();
        } else {
            echo "<H3>No tracks found</H3>\n";
            if($m)
                echo "Hint: Uncheck \"Exact match only\" box to broaden search.";
        }
    }
    
    private function searchByTrack() {
        $libraryAPI = Engine::api(ILibrary::class);
    
        $this->searchForm("Track Search Results");
    
        $p = $_REQUEST["p"];
        if($p == "") $p = 0;
        $search = $this->searchString($_REQUEST["n"], $_REQUEST["m"]);
        $tracks = $libraryAPI->searchPos(ILibrary::TRACK_NAME, $p, $this->maxresults, $search);
    
        $libraryAPI->markAlbumsReviewed($tracks, $this->session->isAuth("u"));
    
        for($i=0; $i < sizeof($tracks); $i++) {
            if (! $opened) {
                if($this->noTables)
                    # 20 20 20 7 7
                    echo "<PRE><B>Artist               Album                Track Name           Coll    Medium  </B>\n";
                else
                    echo "<TABLE CELLPADDING=2>\n  <TR><TH>Artist</TH><TH></TH><TH>Album</TH><TH>Track Name</TH><TH>Collection</TH><TH COLSPAN=2>Medium</TH><TH>Label</TH></TR>\n";
                $opened = 1;
            }
            $count++;
            $trackName = $tracks[$i]["track"];
            $albumName = $tracks[$i]["album"];
            $artistName = $tracks[$i]["artist"];
    
            // Secondary search for album
            $album = $libraryAPI->search(ILibrary::ALBUM_KEY, 0, 1, $tracks[$i]["tag"]);
            if(sizeof($album)) {
                // Artist
                if(!$this->noTables)
                    echo "  <TR><TD>";
                if(!($artistName || $this->noTables))
                    echo "&nbsp;";
                echo "<A HREF=\"".
                                "?s=byArtist&amp;n=". UI::URLify($artistName).
                                "&amp;action=search&amp;session=".$this->session->getSessionID().
                                "&amp;q=". $this->maxresults.
                                "\">";
                echo UI::HTMLify($artistName, 20), "</A>";
                // Album
                if(!$this->noTables) {
                    echo "</TD><TD>";
                    if($tracks[$i]["REVIEWED"])
                        echo "<A HREF=\"".
                             "?s=byAlbumKey&amp;n=". UI::URLify($tracks[$i]["tag"]).
                             "&amp;q=". $this->maxresults.
                             "&amp;action=search&amp;session=".$this->session->getSessionID().
                             "\"><IMG SRC=\"img/rinfo_beta.gif\" " .
                             "ALT=\"Album Review\" " .
                             "WIDTH=12 HEIGHT=11 BORDER=0></A></TD><TD>";
                    else
                        echo "</TD><TD>";
                }
                echo "<A HREF=\"".
                               "?s=byAlbumKey&amp;n=". UI::URLify($album[0]["tag"]).
                               "&amp;q=". $this->maxresults.
                               "&amp;action=search&amp;session=".$this->session->getSessionID().
                               "\">";
                echo UI::HTMLify($albumName, 20). "</A>";
                if(!$this->noTables)
                    echo "</TD><TD>";
                // Track Name
                echo UI::HTMLify($trackName, 20);
                if(!$this->noTables)
                    echo "</TD><TD>";
                // Genre
                switch($album[0]["location"]) {
                case 'G':
                    echo "<I>Deep&nbsp;Storage&nbsp;".$album[0]["bin"]."</I>";
                    break;
                case 'M':
                    echo "<I>Missing</I>";
                    break;
                case 'E':
                    echo "<I>Review Shelf</I>";
                    break;
                case 'F':
                    echo "<I>Out for Review</I>";
                    break;
                case 'U':
                    echo "<I>Deaccessioned</I>";
                    break;
                default:
                    echo UI::HTMLify(Search::GENRES[$album[0]["category"]], 7);
                    break;
                }
                if(!$this->noTables)
                    echo "</TD><TD>";
                // Medium & Length
                echo UI::HTMLify(Search::MEDIA[$album[0]["medium"]], 3);
                if(!$this->noTables)
                    echo "</TD><TD>";
                echo UI::HTMLify(Search::LENGTHS[$album[0]["size"]], 3);
                if(!$this->noTables)
                    echo "</TD><TD>";
                // Label
                if (($album[0]["pubkey"] != 0) && !$this->noTables) {
                    $labelKey = $album[0]["pubkey"];
                    echo "<A HREF=\"".
                                   "?s=byLabelKey&amp;n=". UI::URLify($labelKey).
                                   "&amp;q=". $this->maxresults.
                                   "&amp;action=search&amp;session=".$this->session->getSessionID().
                                   "\">";
                    if(!$labelCache[$labelKey]) {
                        // Tertiary search for label name
                        $label = $libraryAPI->search(ILibrary::LABEL_PUBKEY, 0, 1, $labelKey);
                        if(sizeof($label))
                            $labelCache[$labelKey] = $label[0]["name"];
                        else
                            $labelCache[$labelKey] = "(Unknown)";
                    }
                    echo UI::HTMLify($labelCache[$labelKey], 20). "</A></TD></TR>\n";
                } else {
                    if(!$this->noTables)
                        echo "Unknown</TD></TR>";
                    echo "\n";
                }
            }
        }
        if($opened && $p>0) {
            echo $this->closeList();
            echo "<P><A HREF=\"".
                                  "?s=byTrack&amp;n=". UI::URLify($_REQUEST["n"]).
                                  "&amp;p=". UI::URLify($p),
                                  "&amp;q=". $this->maxresults.
                                  "&amp;action=search&amp;session=".$this->session->getSessionID().
                                  "\">[Next $this->maxresults albums &gt;&gt;]</A>\n";
            $closed = 1;
        }
    
        if(!$closed) {
            $p = 0;
            $this->searchByCollTrack($opened, $this->maxresults - $count);
        }
    }
    
    private function searchByLabel() {
        $this->searchForm("Label Search Results");
        $p = $_REQUEST["p"];
        if($p == "") $p = 0;
        $search = $this->searchString($_REQUEST["n"],$_REQUEST["m"]);
        $labels = Engine::api(ILibrary::class)->searchPos(ILibrary::LABEL_NAME, $p, $this->maxresults, $search);
        for($i=0; $i < sizeof($labels); $i++) {
            if (! $opened ) {
                if($this->noTables)
                    # 20 20 12
                    echo "<PRE><B>Name                 Location             Last Updated</B>\n";
                else
                    echo "<TABLE CELLPADDING=2>\n  <TR><TH>Name</TH><TH COLSPAN=2>Location</TH><TH>Last Updated</TH></TR>\n";
                $opened = 1;
            }
            // Name
            if(!$this->noTables) {
                echo "  <TR><TD>";
                if(!$labels[$i]["name"])
                    echo "&nbsp;";
            }
            echo "<A HREF=\"".
                            "?s=byLabelKey&amp;n=". UI::URLify($labels[$i]["pubkey"]).
                            "&amp;q=". $this->maxresults.
                            "&amp;action=search&amp;session=".$this->session->getSessionID().
                            "\">";
            echo UI::HTMLify($labels[$i]["name"], 20). "</A>";
            if(!$this->noTables)
                echo "</TD><TD>";
            // City
            if(!(strlen($labels[$i]["city"]) || $this->noTables))
                echo "&nbsp;";
            echo UI::HTMLify($labels[$i]["city"], 15);
            if(!$this->noTables)
                echo "</TD><TD>";
            if (preg_match("/t/i", $labels[$i]["international"])) {
                // Foreign label
                //
                // Country
                if(!(strlen($labels[$i]["zip"]) || $this->noTables))
                    echo "&nbsp;";
                echo UI::HTMLify($labels[$i]["zip"], 4);
                if(!$this->noTables);
                    echo "</TD><TD>";
            } else {
                // Domestic label
                //
                // State
                if(!(strlen($labels[$i]["state"]) || $this->noTables))
                    echo "&nbsp;";
                echo UI::HTMLify($labels[$i]["state"], 4);
                if(!$this->noTables)
                    echo "</TD><TD>";
            }
            // Last Update
               if(!(strlen($labels[$i]["modified"]) || $this->noTables))
                echo "&nbsp;";
            echo UI::HTMLify($labels[$i]["modified"], 12);
            if(!$this->noTables)
                echo "</TD></TR>";
            echo "\n";
        }
        if($opened && $p>0) {
            echo $this->closeList();
            $m = $_REQUEST["m"];
            if($m)
                $m = "&m=1";
            echo "<P><A HREF=\"".
                              "?s=byLabel&amp;n=". UI::URLify($_REQUEST["n"]).
                              "&amp;p=". UI::URLify($p). $m.
                              "&amp;q=". $this->maxresults.
                              "&amp;action=search&amp;session=".$this->session->getSessionID().
                              "\">[Next $this->maxresults labels &gt;&gt;]</A>\n";
            $closed = 1;
        }
    
        if ($opened) {
            if(!$closed)
                echo $this->closeList();
        } else {
            echo "<H3>No labels found</H3>\n";
            if($_REQUEST["m"])
                echo "Hint: Uncheck \"Exact match only\" box to broaden search.";
        }
    }
    
    private function searchByLabelKey() {
        $p = $_REQUEST["p"];
        if($p == "") $p = 0;
        $this->searchForm("Label Search Results");
        $search = $this->searchString($_REQUEST["n"], 1);
        $albums = Engine::api(ILibrary::class)->searchPos(ILibrary::ALBUM_PUBKEY, $p, $this->maxresults, $search);
        $this->outputAlbums("byLabelKey", $search, $albums, $p);
    }
}

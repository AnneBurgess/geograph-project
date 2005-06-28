<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
require_once('geograph/feedcreator.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');
	
	
$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS');

$format="RSS1.0";
if (isset($_GET['format']) && in_array($_GET['format'], $valid_formats))
{
	$format=$_GET['format'];
}

if (isset($_GET['i']) && is_numeric($_GET['i'])) {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$_GET['i']}-{$format}.xml";
} else {
	$rssfile=$_SERVER['DOCUMENT_ROOT']."/rss/{$format}.xml";
}

$rss = new UniversalFeedCreator(); 
$rss->useCached($rssfile); 
$rss->title = 'Geograph.co.uk'; 
$rss->link = "http://{$_SERVER['HTTP_HOST']}";
 


if (isset($_GET['i']) && is_numeric($_GET['i'])) {
	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
		
		$pg = $_GET['page'];
		if ($pg == '' or $pg < 1) {$pg = 1;}
		
	$images = new SearchEngine($_GET['i']);
	
	$rss->description = "Images".$images->criteria->searchdesc; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?format=$format&amp;i=".$_GET['i'].(($pg>1)?"&amp;page=$pg":'');
	
	$images->ExecuteCached($pg);
	
	$images->images = &$images->results;
	
} else {
	$rss->description = 'Latest images and news'; 
	$rss->syndicationURL = "http://{$_SERVER['HTTP_HOST']}/syndicator.php?format=$format";


	//lets find some recent photos
	$images=new ImageList(array('accepted', 'geograph'), 'submitted desc', 15);
}
#print "<PRE>";
#	var_dump($images);
#	exit;

#$image = new FeedImage(); 
#$image->title = "dailyphp.net logo"; 
#$image->url = "http://www.dailyphp.net/images/logo.gif"; 
#$image->link = "http://www.dailyphp.net"; 
#$image->description = "Feed provided by dailyphp.net. Click to visit."; 
#$rss->image = $image; 


$cnt=count($images->images);

//create some feed items
for ($i=0; $i<$cnt; $i++)
{
	
	$item = new FeedItem(); 
	$item->title = $images->images[$i]->grid_reference." : ".$images->images[$i]->title; 
	$item->link = "http://{$_SERVER['HTTP_HOST']}/photo/{$images->images[$i]->gridimage_id}";
	if ($images->images[$i]->dist_string || $images->images[$i]->imagetakenString) {
		$item->description = $images->images[$i]->dist_string.($images->images[$i]->imagetakenString?' Taken: '.$images->images[$i]->imagetakenString:'')."<br/>".$images->images[$i]->comment; 
	} else {
		$item->description = $images->images[$i]->comment; 
	}
	$item->date = strtotime($images->images[$i]->submitted); 
	$item->source = "http://{$_SERVER['HTTP_HOST']}/"; 
	$item->author = $images->images[$i]->realname; 
	     
    $rss->addItem($item); 
	

}

//now output the result
header("Content-Type:text/xml");

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past 
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
                                                    // always modified 
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");          



$rss->saveFeed($format, $rssfile); 

?>
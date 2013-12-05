<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Rudi Winter (http://www.geograph.org.uk/profile/2520)
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
 

if ((!preg_match('/\/geotrips\/\d+/',$_SERVER["REQUEST_URI"]) && isset($_GET['trip'])) || strlen($_GET['trip']) !== strlen(intval($_GET['trip']))) {
        //keep urls nice and clean - esp. for search engines!
        header("HTTP/1.0 301 Moved Permanently");
        header("Status: 301 Moved Permanently");
        header("Location: /geotrips/".intval($_GET['trip']));
        print "<a href=\"http://{$_SERVER['HTTP_HOST']}/geotrips/".intval($_GET['trip'])."\">Continue to view this trip</a>";
        exit;
}


if ($_SERVER['SERVER_ADDR']=='127.0.0.1') {
	require_once('./geograph_snub.inc.php');
} else {
	require_once('geograph/global.inc.php');
}
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');

init_session();

$smarty = new GeographPage;

include('./geotrip_func.php');
$db = GeographDatabaseConnection(true);



  // get track from database
  $trk=$db->getRow("select * from geotrips where id=".intval($_GET['trip']));
  $foll=$db->getRow("select id from geotrips where contfrom=".intval($_GET['trip']));
  
  if (empty($trk)) {
    header("HTTP/1.0 404 Not Found");
    $smarty->display('static_404.tpl');
    exit;
  }
  

  if (!empty($trk['title'])) $hdr2=$trk['title'];
  else $hdr2=$trk['location'].' from '.$trk['start'];
  
$smarty->assign('page_title', $hdr2.' :: Geo-Trips');
$smarty->assign('meta_description', "A ".whichtype($trk['type'])." near $trk[location], starting from $trk[start], with pictures and plotted on a map.");

$smarty->display('_std_begin.tpl','trip'.$trk['id']);
print '<link rel="stylesheet" type="text/css" href="/geotrips/geotrips.css" />';


?>

<script src="http://osopenspacepro.ordnancesurvey.co.uk/osmapapi/openspace.js?key=A493C3EB96133019E0405F0ACA6056E3&debug=true" type="text/javascript"></script>

<?php
  $bbox=explode(' ',$trk['bbox']);
  $cen[0]=(int)(($bbox[0]+$bbox[2])/2);
  $cen[1]=(int)(($bbox[1]+$bbox[3])/2);
  if ($bbox[2]-$bbox[0]>4000||$bbox[3]-$bbox[1]>3000) $scale=7;
  else $scale=8;
  $track=explode(' ',$trk['track']);
  $len=count($track);
  // fetch Geograph data
	$engine = new SearchEngine($trk['search']);
	$engine->criteria->resultsperpage = 250; // FIXME really?
	$recordSet = $engine->ReturnRecordset(0, true);
	while (!$recordSet->EOF) {
		$image = $recordSet->fields;
		if (    $image['nateastings']
		    &&  $image['viewpoint_eastings']
		    &&  $image['user_id'] == $trk['uid']
		    &&  $image['viewpoint_grlen'] > 4
		    &&  $image['natgrlen'] > 4
		    && (   $image['view_direction'] != -1
		        || $image['viewpoint_eastings']  != $image['nateastings']
		        || $image['viewpoint_northings'] != $image['natnorthings'])
		    &&  $image['imagetaken'] === $trk['date']
		) {
			$geograph[] = $image;
		}
		$recordSet->MoveNext();
	}
?>

<script type="text/javascript">
  var osMap;
  var trkLayer,trk,trkFeature,trkString;                             // track
  var vdir,vdirFeature,vdirString;                                   // view directions
  var style_trk={strokeColor:"#000000",strokeOpacity:.7,strokeWidth:4.};
  var style_vdir={strokeColor:"#0000ff",strokeOpacity:1.,strokeWidth:2.};
  function initmap() {
    osMap=new OpenSpace.Map('map',{products: ["OV0", "OV1", "OV2", "MSR", "MS", "250KR", "250K", "50KR", "50K", "25KR", "25K", "VMLR", "VML"], controls:[],centreInfoWindow:false});
    osMap.addControl(new OpenSpace.Control.PoweredBy());             //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.CopyrightCollection());   //  needed for T/C compliance
    osMap.addControl(new OpenSpace.Control.SmallMapControl());       //  compass and zoom buttons
    osMap.addControl(new OpenLayers.Control.Navigation({'zoomBoxEnabled':true}));  //  mouse panning, shift-mouse to zoom into box
    <?php print("osMap.setCenter(new OpenSpace.MapPoint($cen[0],$cen[1]),$scale);\n"); ?>
    trkLayer=osMap.getVectorLayer();
    <? if (!empty($trk['track']) && $len>0) { ?>
    // Define track
    trk=new Array();
    <?php for ($i=0;$i<$len-1;$i+=2) print("trk.push(new OpenLayers.Geometry.Point({$track[$i]},{$track[$i+1]}));\n"); ?>
    trkString=new OpenLayers.Geometry.LineString(trk);
    trkFeature=new OpenLayers.Feature.Vector(trkString,null,style_trk);
    trkLayer.addFeatures([trkFeature]);
<?php 
    }
    $len=count($geograph);
    for ($i=0;$i<$len;$i++) {
      // shift marker to centre of square indicated by GR
      fake_precision($geograph[$i]);
      $image = new GridImage();
      $image->fastInit($geograph[$i]);
?>
      // Define camera marker
      pos=new OpenSpace.MapPoint(<?php print("{$geograph[$i]['viewpoint_eastings']},{$geograph[$i]['viewpoint_northings']}");?>);
      size=new OpenLayers.Size(9,9);
      offset=new OpenLayers.Pixel(-4,-9);    // No idea why offset=-9 rather than -4 but otherwise the view line doesn't start at the centre
      infoWindowAnchor=new OpenLayers.Pixel(4,4);
      icon=new OpenSpace.Icon('walk.png',size,offset,null,infoWindowAnchor);
//<![CDATA[
      content='<p>';
      content+='<a href=\"/photo/<?php print($geograph[$i]['gridimage_id']);?>\">';
      content+='<img alt=\"<?php print(sanitise($geograph[$i]['title']));?>\" src=\"';
      content+='<?php print($image->getThumbnail(213,160,true));?>';
      content+='\"></a>';
      content+='</p><p>';
      content+='<strong><?php print(sanitise($geograph[$i]['title']));?></strong>';
      content+='</p><p>';
      content+='<?php print(sanitise($geograph[$i]['comment']));?>';
      content+='</p><p>';
      content+='View full image on ';
      content+='<a href=\"/photo/<?php print($geograph[$i]['gridimage_id']);?>\">';
      content+='Geograph Britain&amp;Ireland</a> ';
      content+='<img alt=\"external link\" title=\"\" src=\"http://<?php echo $CONF['STATIC_HOST']; ?>/img/external.png\" />';
      content+='</p>';
//]]>
      popUpSize=new OpenLayers.Size(300,320);
      osMap.createMarker(pos,icon,content,popUpSize);
      // Define view direction
      vdir=new Array();
      vdir.push(new OpenLayers.Geometry.Point(<?php print("{$geograph[$i]['viewpoint_eastings']},{$geograph[$i]['viewpoint_northings']}");?>));
<?php
      if ($geograph[$i]['nateastings']!=$geograph[$i]['viewpoint_eastings']||$geograph[$i]['natnorthings']!=$geograph[$i]['viewpoint_northings']) {  // subject GR != camera GR
?>
        vdir.push(new OpenLayers.Geometry.Point(<?php print("{$geograph[$i]['nateastings']},{$geograph[$i]['natnorthings']}");?>));
<?php
      } else {
        $ea=$geograph[$i]['nateastings']+round(100.*sin($geograph[$i]['view_direction']*M_PI/180.));
        $no=$geograph[$i]['natnorthings']+round(100.*cos($geograph[$i]['view_direction']*M_PI/180.));
?>
        vdir.push(new OpenLayers.Geometry.Point(<?php print("$ea,$no");?>));
<?php
      }
?>
      vdirString=new OpenLayers.Geometry.LineString(vdir);
      vdirFeature=new OpenLayers.Feature.Vector(vdirString,null,style_vdir);
      trkLayer.addFeatures([vdirFeature]);
<?php
    }
?>
  }
  
  AttachEvent(window,'load',initmap,false);

</script>

<h2><a href="./">Geo-Trips</a> :: <? echo htmlentities($hdr2); ?></h2>

<div class="panel maxi">
<?php
  print('<h3>'.htmlentities($trk['location']).'</h3>');
  $date=date('D, j M Y',strtotime($trk['date']));
  print('<h4>A '.whichtype($trk['type']).' from '.htmlentities($trk['start'])."</h4><h4>$date</h4><h4>by <a href=\"/profile/$trk[uid]\">".htmlentities($trk[user])."</a></h4><p style=\"text-align:center\">");
  // row of random images
  $selected=array();
  for ($i=0;$i<3;$i++) {
    $imgno=mt_rand(0,$len-1);
    if (!in_array($imgno,$selected)) {
      $image = new GridImage();
      $image->fastInit($geograph[$imgno]);
      $thumb=$image->getThumbnail(213,160,true);
      print("<a href=\"/photo/{$geograph[$imgno]['gridimage_id']}\" title=\"".htmlentities($geograph[$imgno]['title'])."\">");
      print("<img alt=\"".htmlentities($geograph[$imgno]['title'])."\" class=\"inner\" src=\"$thumb\" /></a>&nbsp;");
      $selected[]=$imgno;
    } else {
      $i--;
    }
  }
?>
  </p>
<?php
  $prec=$trk['contfrom'];
  $foll=$foll['id'];
  if ($prec||$foll) {
    print('<table class="ruled" style="margin:auto"></tr>');
    if ($prec) print("<td class=\"hlt\" style=\"width:120px;text-align:center\"><a href=\"/geotrips/$prec\">preceding leg</a></td>");
    else print('<td></td>');
    print('<td style="margin:20px;text-align:center"><b>This trip is part of a series.</b></td>');
    if ($foll) print("<td class=\"hlt\" style=\"width:120px;text-align:center\"><a href=\"/geotrips/$foll\">next leg</a></td>");
    else print('<td></td>');
    print('</tr></table>');
  }
?>
  <p>
<?php print(str_replace("\n",'</p><p>',GeographLinks(htmlentities($trk['descr'])))); ?>
  </p>
<? if ($trk['uid'] == $USER->user_id) { ?>
  <div class="inner flt_r">
    [<a href="geotrip_edit.php?trip=<? echo $trk['id']; ?>">edit this trip</a>]
  </div>
<? } ?>
  <div> <p><small>
<?php if ($trk['track']) print('On the map below, the grey line is the GPS track from this trip. ');?>
Click the blue circles to see a photograph
taken from that spot and read further information about the location.  The blue lines indicate
the direction of view.  There is also a
<a href="/search.php?i=<?php print($trk['search']);?>&amp;displayclass=slidebig">slideshow</a> of this trip.
  </small></p></div>
  <div class="row"></div>
  <div id="map" class="inner" style="width:798px;height:800px"></div>
  <p style="font-size:.65em">
All images &copy; <?php print("<a href=\"/profile/{$trk['uid']}\">".htmlentities($trk['user'])."</a>");?> and available under a <a href="http://creativecommons.org/licenses/by-sa/2.0/">
Creative Commons licence</a> <img alt="external link" title="" src="http://<?php echo $CONF['STATIC_HOST']; ?>/img/external.png" />.
  </p>
</div>

<?php 

$smarty->display('_std_end.tpl');


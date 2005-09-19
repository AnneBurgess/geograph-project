<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
$db2 = NewADOConnection($GLOBALS['DSN']);

require_once('geograph/conversions.class.php');
$conv = new Conversions;

//this takes a long time, so we output a header first of all
$smarty->display('_std_begin.tpl');

?>
<h2>gridimage_search Rebuild Tool</h2>
<form action="buildgridimage_search.php" method="post">
<input type="checkbox" id="recreate" name="recreate" value="1" checked="checked">
<label for="recreate">Recreate entire gridimage_search table from gridimage table</label><br>

<input type="checkbox" id="update" name="update" value="1" checked="checked">
<label for="update">Update lat/long values in gridimage_search</label><br>

<input type="submit" name="go" value="Start">
</form>

<?php


if (isset($_POST['recreate']))
{
	echo "<h3>Rebuilding gridimage_search from gridimage</h3>";
	flush();
	set_time_limit(3600*24);
	
	$db->Execute("TRUNCATE gridimage_search");
	
	$db->Execute("INSERT INTO gridimage_search
		SELECT gridimage_id, gi.user_id, moderation_status, title, submitted, imageclass, imagetaken, upd_timestamp, x, y, gs.grid_reference, user.realname,reference_index,comment,0,0,ftf,seq_no
		FROM gridimage AS gi
		INNER JOIN gridsquare AS gs
		USING ( gridsquare_id ) 
		INNER JOIN user ON ( gi.user_id = user.user_id ) 
		WHERE moderation_status != 'rejected' ");
	echo "<p>Rebuild completed</p>";
	
}

if (isset($_POST['update']))
{
	
	echo "<h3>Updating Lat/Long</h3>";
	flush();
	
	$start = time();

	$recordSet = &$db->Execute("select gridimage_id,x,y,reference_index,nateastings,natnorthings ". 
		"from gridimage ".
		"INNER JOIN gridsquare AS gs USING ( gridsquare_id ) ".
		"where moderation_status != 'rejected'");
	$count=0;
	while (!$recordSet->EOF) 
	{
		$image = $recordSet->fields;
	
		if ($image['nateastings']) {
			list($lat,$long) = $conv->national_to_wgs84($image['nateastings'],$image['natnorthings'],$image['reference_index']);
		} else {
			list($lat,$long) = $conv->internal_to_wgs84($image['x'],$image['y'],$image['reference_index']);
		}
	
		$db2->Execute("UPDATE gridimage_search SET wgs84_lat = $lat, wgs84_long = $long WHERE gridimage_id = ".$image['gridimage_id']);
		
		if (++$count%100==0) {
				printf("done %d at <b>%d</b> seconds<br/>",$count,time()-$start);
				flush();
		}
		$recordSet->MoveNext();
	}
	
	echo "<p>Lat/Long update complete</p>";
	
}


	$smarty->display('_std_end.tpl');
	exit;
	


	
?>

<?php
/**
 * $Project: GeoGraph $
 * $Id: show_exif.php 5875 2009-10-20 17:43:17Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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

$smarty = new GeographPage;
$template='snippets.tpl';	

$USER->mustHavePerm("basic");

$gid = 0;

if (!empty($_REQUEST['gridimage_id'])) {

	$gid = intval($_REQUEST['gridimage_id']);
	
	$image=new GridImage();
	$ok = $image->loadFromId($gid);
		
	if (!$ok) {
		die("invalid image");
	} elseif ($image->user_id != $USER->user_id && !$USER->hasPerm('moderator')) {
		die("unable to access this image");
	}
	
	$smarty->assign('gridimage_id',$gid);
}

$db = GeographDatabaseConnection(false);

if (!empty($_REQUEST['edit'])) {

	if (!empty($_POST['cancel'])) {
		header("Location: {$_SERVER['PHP_SELF']}?".preg_replace('/edit[\[\d\]%bdBD]+=\w+/','',$_SERVER['QUERY_STRING']));
		print "<a href=\"{$_SERVER['PHP_SELF']}\">continue</a>";
		exit;
	}

	$snippet_id = intval(array_pop(array_keys($_REQUEST['edit'])));
	
	$data = $db->getRow("SELECT s.*,COUNT(gs.snippet_id) AS images,SUM(gs.user_id = {$USER->user_id}) AS yours FROM snippet s LEFT JOIN gridimage_snippet gs ON (s.snippet_id = gs.snippet_id AND gridimage_id < 4294967296) WHERE s.snippet_id = $snippet_id GROUP BY s.snippet_id");
	
	if (!$USER->hasPerm('moderator') && $data['user_id'] != $USER->user_id) {
		die("not your snippet");
	}
	
	if (!empty($_POST['save'])) {
		$errors = array();
		$updates = array();
		
		if (!empty($_POST['title'])) {
			if ($data['title'] != $_POST['title']) 
				$updates['title'] = $_POST['title'];
		} else {
			$errors['title'] = "Title can't be empty";
		}
		if ($data['comment'] != $_POST['comment']) {
			$updates['comment'] = $_POST['comment'];
		}
		
		$square=new GridSquare;
		if (!empty($_POST['grid_reference']) && $data['grid_reference'] != $_POST['grid_reference'] && $square->setByFullGridRef($_POST['grid_reference'],true) ) {

			require_once('geograph/conversions.class.php');
			$conv = new Conversions;
			list($lat,$long) = $conv->gridsquare_to_wgs84($square);

			if (!empty($_POST['grid_reference'])) {
				//we store these so can recreate the original GR - but only if specifically entered
				$updates['nateastings'] = $square->nateastings;
				$updates['natnorthings'] = $square->natnorthings;
				$updates['natgrlen'] = $square->natgrlen;
			}
			$updates['reference_index'] = $square->reference_index;
			
			//for the sphinx index
			$updates['grid_reference'] = $square->grid_reference;

			$updates['wgs84_lat'] = $lat;
			$updates['wgs84_long'] = $long;

			//for mysql indexing (where sphinx not available) 
			$point = "point_en=GeomFromText('POINT({$square->nateastings} {$square->natnorthings})'),";
		} else {
			$point = "";
		}
		
		
		
		if (!$errors) {
		
				
			$db->Execute("UPDATE snippet SET $point`".implode('` = ?,`',array_keys($updates)).'` = ? WHERE snippet_id = '.$snippet_id,array_values($updates));
	
			foreach ($updates as $key => $value) {
				if ($value != $data[$key]) {
					$inserts = array();
					$inserts['snippet_id'] = $snippet_id;
					$inserts['user_id'] = $USER->user_id;
					$inserts['field'] = $key;
					$inserts['oldvalue'] = $data[$key];
					$inserts['newvalue'] = $value;
					
					$db->Execute('INSERT INTO snippet_item SET `'.implode('` = ?,`',array_keys($inserts)).'` = ?',array_values($inserts));
				}
			}
			header("Location: {$_SERVER['PHP_SELF']}?".preg_replace('/edit[\[\d\]%bdBD]+=\w+/','thankyou=saved',$_SERVER['QUERY_STRING']));
			print "<a href=\"{$_SERVER['PHP_SELF']}\">continue</a>";
			exit;
		} else {
			$smarty->assign('errors',$errors);
			$smarty->assign($_POST);
			
			$smarty->assign('edit',1);
		}
	
	} else {
		if ($data['nateastings']) {
			require_once('geograph/conversions.class.php');
			$conv = new Conversions;

			list($gr,$len) = $conv->national_to_gridref(
				$data['nateastings'],
				$data['natnorthings'],
				max(4,$data['natgrlen']),
				$data['reference_index'],false);
			$data['grid_reference'] = $gr;
		}
	
		$smarty->assign($data);

		$smarty->assign('edit',1);
	}

} elseif (!empty($_POST['delete'])) {
	
	$where = '';
	if (!$USER->hasPerm('moderator')) {
		$where = " user_id = {$USER->user_id} AND ";
	}
	
	
	foreach ($_POST['delete'] as $id => $text) {
		
	
		
		$db->Execute("UPDATE snippet SET enabled = 0 WHERE $where snippet_id = ".intval($id));
	}

}


if (empty($_REQUEST['edit']) && (!empty($_REQUEST['gr']) || !empty($_REQUEST['q']))) {
	$square=new GridSquare;
	
	$grid_given=true;
	if ($grid_ok=$square->setByFullGridRef($_REQUEST['gr'],true)) {
	
		$smarty->assign('gr',$_REQUEST['gr']);
		
		if ($square->natgrlen > 4) {
			$smarty->assign('centisquare',1);
		}
		
	} elseif (!empty($_REQUEST['gr'])) {
		print "invalid GR!";
	}
	$where = array();
	$fields = '';
	$orderby = "ORDER BY s.snippet_id";
	if ($CONF['sphinx_host']) {
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		
		if (!empty($_REQUEST['page'])) {
			$pg = intval($_REQUEST['page']);
		} else {
			$pg = 1;
		}
		
		$q=trim($_REQUEST['q']);
		
		$sphinx = new sphinxwrapper($q);
		$sphinx->pageSize = $pgsize = 25;

		if (preg_match('/\bp(age|)(\d+)\s*$/',$q,$m)) {
			$pg = intval($m[2]);
			$sphinx->q = preg_replace('/\bp(age|)\d+\s*$/','',$sphinx->q);
		}

		$smarty->assign('q', $sphinx->qclean);
		if ($q) {
			$title = "Matching word search [ ".htmlentities($sphinx->qclean)." ]";
		}
		
		$data = array();
		$data['x'] = $square->x;
		$data['y'] = $square->y;
		if ($square->natgrlen > 4) {
			list($data['lat'],$data['long']) = $conv->gridsquare_to_wgs84($square);
		}
		$data['d'] = !empty($_REQUEST['radius'])?floatval($_REQUEST['radius']):1;
		$data['sort'] = "@geodist ASC, @relevance DESC, @id DESC";
		
		$sphinx->setSort($data['sort']);
		$sphinx->setSpatial($data);
		
		$filters = array();
		if (!$USER->hasPerm('moderator') || !empty($_REQUEST['onlymine'])) {
			$filters['user_id'] = array($USER->user_id);
			$smarty->assign("onlymine",1);
		}
		if (!empty($filters)) {
			$sphinx->addFilters($filters);
		}
		
		$ids = $sphinx->returnIds($pg,'snippet');

		$smarty->assign("query_info",$sphinx->query_info);

		if (!empty($ids) && count($ids)) {
			$id_list = implode(',',$ids);
			$where[] = "s.snippet_id IN($id_list)";
			$orderby = "ORDER BY FIELD(s.snippet_id,$id_list)";
			
		} else {
			$where[] = '0';
		}
	} else {
		$radius = !empty($_REQUEST['radius'])?intval($_REQUEST['radius']*1000):1000;

		$left=$square->nateastings-$radius;
		$right=$square->nateastings+$radius;
		$top=$square->natnorthings-$radius;
		$bottom=$square->natnorthings+$radius;

		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

		$fields = ",if(natnorthings > 0,(nateastings-{$square->nateastings})*(nateastings-{$square->nateastings})+(natnorthings-{$square->natnorthings})*(natnorthings-{$square->natnorthings}),0) as distance";
		
		$where[] = "CONTAINS(
				GeomFromText($rectangle),
				point_en)";
		
		if (!$USER->hasPerm('moderator')) {
			$where[] = "s.user_id = {$USER->user_id}";
			$smarty->assign("onlymine",1);
		}
		
		if (!empty($_REQUEST['q'])) {
			$q=mysql_real_escape_string(trim($_REQUEST['q']));
			
			$where[] = "(title LIKE '%$q%' OR comment LIKE '%$q%')";
			$smarty->assign('q',trim($_POST['q']));
		}
	}
	
	$smarty->assign_by_ref('radius',$_REQUEST['radius']);
	
	
	$where[] = "enabled = 1"; 
	$where= implode(' AND ',$where);
	
	$results = $db->getAll($sql="SELECT s.*,realname,COUNT(gs.snippet_id) AS images,SUM(gs.user_id = {$USER->user_id}) AS yours $fields FROM snippet s INNER JOIN user u USING (user_id) LEFT JOIN gridimage_snippet gs ON (s.snippet_id = gs.snippet_id AND gridimage_id < 4294967296) WHERE $where GROUP BY s.snippet_id $orderby"); 
	
	list($usec, $sec) = explode(' ',microtime());
	$querytime_after = ((float)$usec + (float)$sec);
	
	#$smarty->assign("query_info", "time: ".($querytime_after - $querytime_before));
	
	if ($fields) {
		foreach ($results as $id => $row) {
			if ($row['distance'] > 0)
				$results[$id]['distance'] = round(sqrt($row['distance'])/1000)+0.01;
		}
	}
	
	$smarty->assign_by_ref('grid_reference',$square->grid_reference);
	$smarty->assign_by_ref('results',$results);
} 

if (!empty($_GET['thankyou'])) {
	$smarty->assign('thankyou',$_GET['thankyou']);
}


if ($CONF['sphinx_host']) {
	$smarty->assign('sphinx',1);
}





$smarty->display($template);

?>

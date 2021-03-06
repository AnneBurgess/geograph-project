<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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
include_messages('moversboard');
init_session();


$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'images';


$smarty = new GeographPage;

$template='statistics_moversboard.tpl';
$cacheid=$type;

if (!$smarty->is_cached($template, $cacheid))
{
	/////////////
	// in the following code 'geographs' is used a column for legacy reasons, but dont always represent actual geographs....

	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed'); 

	$sql_qtable = array (
		'squares' => array('column' => ''),
		'geosquares' => array('column' => ''),
		'geographs' => array(
			'column' => "sum(i.moderation_status='geograph')",
		),
		'additional' => array(
			'column' => "sum(i.moderation_status='geograph' and ftf = 0)",
		),
		'supps' => array(
			'column' => "sum(i.moderation_status='accepted')",
		),
		'images' => array(
			'orderby' => ",points desc",
			'column' => "sum(i.ftf=1 and i.moderation_status='geograph') as points, sum(i.moderation_status in ('geograph','accepted'))",
		),
		'test_points' => array(
			'column' => "sum((i.moderation_status = 'geograph') + ftf + 1)",
			'table' => " gridimage_search i ",
		),
		'depth' => array(
			'column' => "count(*)/count(distinct grid_reference)",
			'table' => " gridimage_search i ",
			'isfloat' => true,
		),
		'myriads' => array(
		//we dont have access to grid_reference - possibly join with grid_prefix, but for now lets just exclude pending!
			'column' => "count(distinct substring(grid_reference,1,length(grid_reference)-4))",
			'table' => " gridimage_search i ",
		),
		'hectads' => array(
		//we dont have access to grid_reference - possibly join with grid_prefix, but for now lets just exclude pending!
			'column' => "count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )",
			'table' => " gridimage_search i ",
		),
		'days' => array(
			'column' => "count(distinct imagetaken)",
			'table' => " gridimage_search i ",
		),
		'antispread' => array(
			//we dont have access to grid_reference - possibly join with grid_prefix, but for now lets just exclude pending!
			'column' => "count(*)/count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )",
			'table' => " gridimage_search i ",
			'isfloat' => true,
		),
		'spread' => array(
			//we dont have access to grid_reference - possibly join with grid_prefix, but for now lets just exclude pending!
			'column' => "count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )/count(*)",
			'table' => " gridimage_search i ",
			'isfloat' => true,
		),
		'classes' => array(
			'column' => "count(distinct imageclass)",
			'table' => " gridimage_search i ",
		),
		'clen' => array(
			'column' => "avg(length(comment))",
			'table' => " gridimage_search i ",
			'isfloat' => true,
		),
		'tlen' => array(
			'column' => "avg(length(title))",
			'table' => " gridimage_search i ",
			'isfloat' => true,
		),
		'category_depth' => array(
			'column' => "count(*)/count(distinct imageclass)",
			'table' => " gridimage_search i ",
			'isfloat' => true,
		),
		'centi' => array(
		//NOT USED AS REQUIRES A NEW INDEX ON gridimage!
			'column' => "COUNT(DISTINCT nateastings div 100, natnorthings div 100)",
			'where' => "and i.moderation_status='geograph' and nateastings div 1000 > 0",
		),
		'points' => array(
			'column' => "sum(i.ftf=1 and i.moderation_status='geograph')",
		),
	);

	if (!isset($sql_qtable[$type])) {
		$type = 'points';
	}

	$isfloat = false;
	if (isset($sql_qtable[$type]['isfloat'])) $isfloat = $sql_qtable[$type]['isfloat'];

	$smarty->assign('heading', $MESSAGES['moversboard']['headings'][$type]);
	$smarty->assign('desc', $MESSAGES['moversboard']['descriptions'][$type]);
	$smarty->assign('type', $type);
	$smarty->assign('isfloat', $isfloat);

	$sql_column = '';
	$sql_orderby = '';
	$sql_table = " gridimage as i ";
	$sql_where = '';
	if ($sql_qtable[$type]['column'] === '') {
		if ($type == 'geosquares') {
			$sql_where = " and i.moderation_status='geograph'";
		} // else { // $type == 'squares'
		//}
		//squares has to use a count(distinct ...) meaning cant have pending in same query... possibly could do with a funky subquery but probably would lower performance...
		$sql="select i.user_id,u.realname,
		count(distinct grid_reference) as geographs
		from gridimage_search as i 
		inner join user as u using(user_id)  
		where i.submitted > date_sub(now(), interval 7 day) $sql_where
		group by i.user_id 
		order by geographs desc";
		$topusers=$db->GetAssoc($sql);

		$sqlsum="select count(distinct grid_reference) as geographs
		from gridimage_search as i 
		where i.submitted > date_sub(now(), interval 7 day) $sql_where";
		$sum=$db->GetRow($sqlsum);


		//now we want to find all users with pending images and add them to this array
		$sql="select i.user_id,u.realname,0 as geographs, count(*) as pending from gridimage as i
		inner join user as u using(user_id)  
		where i.submitted > date_sub(now(), interval 7 day) and
		i.moderation_status='pending'
		group by i.user_id
		order by pending desc";
		$pendingusers=$db->GetAssoc($sql);
		foreach($pendingusers as $user_id=>$pending) {
			if (isset($topusers[$user_id])) {
				$topusers[$user_id]['pending']=$pending['pending'];
			} else {
				$topusers[$user_id]=$pending;
			}
		}
		//no need to resort the combined array as should have imlicit ordering!
	} else {
		$sql_column = $sql_qtable[$type]['column'];
		if (isset($sql_qtable[$type]['where'])) $sql_where = $sql_qtable[$type]['where'];
		if (isset($sql_qtable[$type]['table'])) $sql_table = $sql_qtable[$type]['table'];
		if (isset($sql_qtable[$type]['orderby'])) $sql_orderby = $sql_qtable[$type]['orderby'];

		$sqlsum="select $sql_column as geographs from $sql_table
		where i.submitted > date_sub(now(), interval 7 day) $sql_where";
		$sum=$db->GetRow($sqlsum);

		$sql_pending = (strpos($sql_table,'_search') === FALSE)?"sum(i.moderation_status='pending')":'0';
		//we want to find all users with geographs/pending images 
		$sql="select i.user_id,u.realname,
		$sql_column as geographs, 
		$sql_pending as pending
		from $sql_table left join user as u using(user_id) 
		where i.submitted > date_sub(now(), interval 7 day) $sql_where
		group by i.user_id 
		having (geographs > 0 or pending > 0)
		order by geographs desc $sql_orderby, pending desc ";
		if ($_GET['debug'])
			print $sql;
		$topusers=$db->GetAssoc($sql);
	}		
	//assign an ordinal

	$i=1;$lastgeographs = '?';
	#$geographs = 0;
	$pending = 0;
	#$points = 0;
	foreach($topusers as $user_id=>$entry)
	{
		if ($lastgeographs == $entry['geographs'])
			$topusers[$user_id]['ordinal'] = '&quot;&nbsp;&nbsp;&nbsp;';
		else {
			$topusers[$user_id]['ordinal'] = smarty_function_ordinal($i);
			$lastgeographs = $entry['geographs'];
		}
		$i++;
		#$geographs += $entry['geographs'];
		$pending += $entry['pending'];
		#$points += $entry['points'];
		if (empty($entry['points'])) $topusers[$user_id]['points'] = '';
	}
	
	
	$geographs=$sum['geographs'];
	$points=$sum['points'];
	$smarty->assign('geographs', $geographs);
	$smarty->assign('pending', $pending);
	$smarty->assign('points', $points);
	
	$smarty->assign_by_ref('topusers', $topusers);
	$smarty->assign('cutoff_time', time()-86400*7);
	
	$smarty->assign('types', array('points','geosquares','images','depth'));
	$smarty->assign('typenames', $MESSAGES['moversboard']['type_names']);
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>

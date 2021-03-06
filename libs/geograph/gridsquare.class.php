<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
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

/**
* Provides the GridSquare class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

include_messages('class_gridsquare');

/**
* GridSquare class
* Provides an abstraction of a grid square, providing all the
* obvious functions you'd expect
*/
class GridSquare
{
	/**
	* internal database handle
	*/
	var $db=null;
	
	/**
	* gridsquare_id primary key
	*/
	var $gridsquare_id=0;

	/**
	* 4figure text grid reference for this square
	*/
	var $grid_reference='';

	/**
	* which grid does this location refer to
	*/
	var $reference_index=0;

	/**
	* internal grid position
	*/
	var $x=0;
	var $y=0;

	/**
	* how much land? (0-100%)
	*/
	var $percent_land=0;
	
	/**
	* how many images in this square
	*/
	var $imagecount=0;
	
	/**
	* exploded gridsquare element of $this->grid_reference
	*/
	var $gridsquare="";
	
	/**
	* exploded eastings element of $this->grid_reference
	*/
	var $eastings=0;
	
	/**
	* exploded northings element of $this->grid_reference
	*/
	var $northings=0;
	
	/**
	* national easting/northing (ie not internal)
	*/
	var $nateastings;
	var $natnorthings;
	var $natgrlen = 0;
	var $natspecified = false;
	
	/**
	* GridSquare instance of nearest square to this one with an image
	*/
	var $nearest=null;
	
	
	/**
	* nearest member will have this set to show distance of nearest square from this one
	*/
	var $distance=0;
	
	
	
	/**
	* Constructor
	*/
	function GridSquare()
	{
	}
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed: '.mysql_error());  
		return $this->db;
	}

	/**
	 * set stored db object
	 * @access private
	 */
	function _setDB(&$db)
	{
		$this->db=$db;
	}
	
	/**
	* store error message
	*/
	function _error($msg)
	{
		$this->errormsg=$msg;
	}
	
	function assignDiscussionToSmarty(&$smarty) 
	{
		global $memcache;
		
		$mkey = $this->gridsquare_id;
		//fails quickly if not using memcached!
		$result =& $memcache->name_get('gsd',$mkey);
		if ($result) {
			$smarty->assign_by_ref('discuss', $result['topics']);
			$smarty->assign('totalcomments', $result['totalcomments']);
			return;
		}
	
		$db=&$this->_getDB();
		
		$sql='select t.topic_id,posts_count-1 as comments,CONCAT(\'Discussion on \',t.topic_title) as topic_title '.
			'from gridsquare_topic as gt '.
			'inner join geobb_topics as t using (topic_id)'.
			'where '.
			"gt.gridsquare_id = {$this->gridsquare_id} ".
			'order by t.topic_time desc';
		
		$topics=$db->GetAll($sql);
		if ($topics)
		{
			$news=array();

			foreach($topics as $idx=>$topic)
			{
				$firstpost=$db->GetRow("select post_text,poster_name,post_time,poster_id from geobb_posts where topic_id={$topic['topic_id']} order by post_time limit 1");
				$topics[$idx]['post_text']=GeographLinks(str_replace('<br>', '<br/>', $firstpost['post_text']));
				$topics[$idx]['realname']=$firstpost['poster_name'];
				$topics[$idx]['user_id']=$firstpost['poster_id'];
				$topics[$idx]['topic_time']=$firstpost['post_time'];
				$totalcomments += $topics[$idx]['comments'] + 1;
			}
			$smarty->assign_by_ref('discuss', $topics);
			$smarty->assign('totalcomments', $totalcomments);
			
			$result = array();
			$result['topics'] = $topics;
			$result['totalcomments'] = $totalcomments;
			
			//fails quickly if not using memcached!
			$memcache->name_set('gsd',$mkey,$result,$memcache->compress,$memcache->period_short);
		}
	}
	
	
	/**
	* Conveience function to get six figure GridRef
	*/
	function get6FigGridRef()
	{
		return sprintf("%s%03d%03d", $this->gridsquare, $this->eastings*10 + 5, $this->northings*10 + 5);
	}

	/**
	* Conveience function to get national easting (not internal)
	*/
	function getNatEastings()
	{
		global $CONF,$memcache;
		
		if (!isset($this->nateastings)) {
			//fails quickly if not using memcached!
			$mkey = $this->gridsquare;
			$square =& $memcache->name_get('pr',$mkey);
			if (!$square) {
				$db=&$this->_getDB();

				$square = $db->GetRow('select origin_x,origin_y from gridprefix where prefix='.$db->Quote($this->gridsquare).' limit 1');
				
				//fails quickly if not using memcached!
				$memcache->name_set('pr',$mkey,$square,$memcache->compress,$memcache->period_short);
			}
			
			//get the first gridprefix with the required reference_index
			//after ordering by x,y - you'll get the bottom
			//left gridprefix, and hence the origin
			
			$square['origin_x'] -= $CONF['origins'][$this->reference_index][0];
			$square['origin_y'] -= $CONF['origins'][$this->reference_index][1];
			
			$this->nateastings = sprintf("%05d",intval($square['origin_x']/100)*100000+ ($this->eastings * 1000 + 500));
			$this->natnorthings = sprintf("%05d",intval($square['origin_y']/100)*100000+ ($this->northings * 1000 +500));
			$this->natgrlen = 4;
		} 
		return $this->nateastings;
	}
	
	/**
	* Conveience function to get national northing (not internal)
	*/
	function getNatNorthings()
	{
		if (!isset($this->natnorthings)) {
			$this->getNatEastings();
		} 
		return $this->natnorthings;
	}
	
	/**
	* Get an array of valid grid prefixes
	*/
	function getGridPrefixes($ri = 0)
	{
		$andwhere = ($ri)?" and reference_index = $ri ":'';
		$db=&$this->_getDB();
		return $db->CacheGetAssoc(3600*24*7,"select prefix,prefix from gridprefix ".
			"where landcount>0 $andwhere".
			"order by reference_index,prefix");

	}
	
	/**
	* Get an array of valid kilometer indexes
	*/
	function getKMList()
	{
		$kmlist=array();
		for ($k=0; $k<100;$k++)
		{
			$kmlist[$k]=sprintf("%02d", $k);
		}
		return $kmlist;
	}
	
	/**
	* Store grid reference in session
	*/
	function rememberInSession()
	{
		if (strlen($this->grid_reference))
		{
			$_SESSION['gridref']=$this->grid_reference;
			$_SESSION['gridsquare']=$this->gridsquare;
			$_SESSION['eastings']=$this->eastings;
			$_SESSION['northings']= $this->northings;
			
		}
	}
	
	/**
	*
	*/
	function setByFullGridRef($gridreference,$setnatfor4fig = false,$allowzeropercent = false,$recalc = false)
	{
		global $CONF, $MESSAGES;
		$matches=array();
		$isfour=false;
		
		if (preg_match("/\b([a-zA-Z]{1,3}) ?(\d{1,5})[ \.](\d{1,5})\b/",$gridreference,$matches) and (strlen($matches[2]) == strlen($matches[3]))) {
			list ($prefix,$e,$n) = array($matches[1],$matches[2],$matches[3]);
			$length = strlen($matches[2]);
			$natgrlen = $length * 2;
		} elseif (preg_match("/\b([a-zA-Z]{1,3}) ?(\d{0,10})\b/",$gridreference,$matches) and ((strlen($matches[2]) % 2) == 0)) {
			$natgrlen = strlen($matches[2]);
			$length = $natgrlen / 2;
			list ($prefix,$e,$n) = array($matches[1], substr($matches[2], 0, $length), substr($matches[2], -$length));
		}

		if (!empty($prefix))
		{
			$gridref=sprintf("%s%02d%02d", strtoupper($prefix), intval($e/1000), intval($n/1000));

			$ok = false;
			if ($recalc) {
				$db=&$this->_getDB();
				$prefix = strtoupper($prefix);
				$sql = "select * from gridprefix where prefix='{$prefix}' limit 1";
				$row = $db->GetRow($sql);
				if (count($row)) {
					require_once('geograph/conversions.class.php');
					$conv = new Conversions;
					$ri = $row['reference_index'];
					$fe = intval($e) + ($row['origin_x']-$CONF['origins'][$ri][0]) * 1000;
					$fn = intval($n) + ($row['origin_y']-$CONF['origins'][$ri][1]) * 1000;
					$latlong = $conv->national_to_wgs84($fe,$fn,$ri);
					if (count($latlong)) {
						$enr = $conv->wgs84_to_national($latlong[0],$latlong[1]);
						if (count($enr)) {
							$ri2 = $enr[2];
							$fe2=$enr[0];$fn2=$enr[1];
							if ($ri2 != $ri) { // we got a new ri
								if ($length == 1)
									$shift = 5000;
								elseif ($length == 0)
									$shift = 50000;
								else
									$shift = 0;
								$e2 = round($enr[0],$length-5) + $shift;
								$n2 = round($enr[1],$length-5) + $shift;
								$x = floor($e2/1000) + $CONF['origins'][$ri2][0];
								$y = floor($n2/1000) + $CONF['origins'][$ri2][1];
								$ok = $this->loadFromPosition($x, $y);
								if ($ok) {
									$prefix = $this->gridsquare;
									$e = sprintf("%05d", $e2%100000);
									$n = sprintf("%05d", $n2%100000);
									$gridref = $this->grid_reference;
								}
							}
						}
					}
				}
			}

			if (!$ok)
				$ok=$this->_setGridRef($gridref,$allowzeropercent);
			if ($ok && (!$isfour || $setnatfor4fig))
			{
				//we could be reassigning the square!
				unset($this->nateastings);
				
				//use this function to work out the major easting/northing then convert to our exact values
				$eastings=$this->getNatEastings();
				$northings=$this->getNatNorthings();
				
				$emajor = floor($eastings / 100000);
				$nmajor = floor($northings / 100000);
	
				$this->nateastings = $emajor.sprintf("%05d",$e);
				$this->natnorthings = $nmajor.sprintf("%05d",$n);
				$this->natgrlen = $natgrlen;
				$this->precision=pow(10,6-($natgrlen/2))/10;
			} else {
				$this->precision=1000;
			}
		} else {
			$ok=false;
			$this->_error(sprintf($MESSAGES['class_gridsquare']['gridref_invalid'], htmlentities($gridreference)));

		}
				
		return $ok;
	}
	
	/**
	* Stores the grid reference along with handy exploded elements 
	*/
	function _storeGridRef($gridref)
	{
		$this->grid_reference=$gridref;
		if (preg_match('/^([A-Z]{1,3})(\d\d)(\d\d)$/',$this->grid_reference, $matches))
		{
			$this->gridsquare=$matches[1];
			$this->eastings=$matches[2];
			$this->northings=$matches[3];
		}
		
	}
	
	
	/**
	* Just checks that a grid position is syntactically valid
	* No attempt is made to see if its a real grid position, just to ensure
	* that the input isn't anything nasty from the client side
	*/
	function validGridPos($gridsquare, $eastings, $northings)
	{
		$ok=true;
		$ok=$ok && preg_match('/^[A-Z]{1,3}$/',$gridsquare);
		$ok=$ok && preg_match('/^[0-9]{1,2}$/',$eastings);
		$ok=$ok && preg_match('/^[0-9]{1,2}$/',$northings);
		return $ok;
	}

	/**
	* set up and validate grid square selection using seperate reference components
	*/
	function setGridPos($gridsquare, $eastings, $northings,$allowzeropercent = false)
	{
		//assume the inputs are tainted..
		$ok=$this->validGridPos($gridsquare, $eastings, $northings);
		if ($ok)
		{
			$gridref=sprintf("%s%02d%02d", $gridsquare, $eastings, $northings);
			$ok=$this->_setGridRef($gridref,$allowzeropercent);
		}
		
		return $ok;
	}

	/**
	* Just checks that a grid position is syntactically valid
	* No attempt is made to see if its a real grid position, just to ensure
	* that the input isn't anything nasty from the client side
	*/
	function validGridRef($gridref, $figures=4)
	{
		return preg_match('/^[A-Z]{1,3}[0-9]{'.$figures.'}$/',$gridref);
	}


	/**
	* set up and validate grid square selection using grid reference
	*/
	function setGridRef($gridref)
	{
		global $MESSAGES;
		$gridref = preg_replace('/[^\w]+/','',strtoupper($gridref)); #assume the worse and remove everything, also not everyone uses the shift key
		//assume the inputs are tainted..
		$ok=$this->validGridRef($gridref);
		if ($ok)
		{
			$ok=$this->_setGridRef($gridref);
		}
		else
		{
			//six figures?
			$matches=array();
			if (preg_match('/^([A-Z]{1,3})(\d\d)\d(\d\d)\d$/',$gridref,$matches))
			{
				$fixed=$matches[1].$matches[2].$matches[3];
				$this->_error(sprintf($MESSAGES['class_gridsquare']['gridref_4fig'], $fixed, $gridref));
			}
			else
			{
				$this->_error(sprintf($MESSAGES['class_gridsquare']['gridref_invalid'], htmlentities($gridref)));
			}
		}
		
		return $ok;
	}
	
	/**
	* load square from database
	*/
	function loadFromId($gridsquare_id)
	{
		$db=&$this->_getDB();
		$square = $db->GetRow('select * from gridsquare where gridsquare_id='.$db->Quote($gridsquare_id).' limit 1');	
		if (count($square))
		{		
			//store cols as members
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->$name=$value;
								
			}
			
			//ensure we get exploded reference members too
			$this->_storeGridRef($this->grid_reference);
			
			return true;
		}
		return false;
	}
	
	/**
	* load square from internal coordinates
	*/
	function loadFromPosition($internalx, $internaly, $findnearest = false)
	{
		global $CONF, $MESSAGES;
		$ok=false;
		$db=&$this->_getDB();
		$square = $db->GetRow("select * from gridsquare where CONTAINS( GeomFromText('POINT($internalx $internaly)'),point_xy ) order by percent_land desc limit 1");
		if (count($square))
		{		
			$ok=true;
			
			//store cols as members
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->$name=$value;
			}
			
			//ensure we get exploded reference members too
			$this->_storeGridRef($this->grid_reference);
			
			//square is good, how many pictures?
			if ($findnearest && $this->imagecount==0)
			{
				//find nearest square for 100km
				$this->findNearby($square['x'], $square['y'], 100);
			}
		} else {
			$this->_error($MESSAGES['class_gridsquare']['outside_area']);
		}
		return $ok;
	}

	/**
	* set up and validate grid square selection
	*/
	function _setGridRef($gridref,$allowzeropercent = false)
	{
		global $CONF, $MESSAGES;
		$ok=true;

		$db=&$this->_getDB();
		
		//store the reference 
		$this->_storeGridRef($gridref);
			
		//check the square exists in database
		$count=0;
		$square = $db->GetRow('select * from gridsquare where grid_reference='.$db->Quote($gridref).' limit 1');	
		if (count($square))
		{		
			//store cols as members
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->$name=$value;
						
			}
			
			if ($this->percent_land==0 && !$allowzeropercent && $this->imagecount==0)
			{
				$this->_error(sprintf($MESSAGES['class_gridsquare']['gridref_outside_area_link'], $gridref));
				$ok=false;

			}
			
			//square is good, how many pictures?
			if ($this->imagecount==0)
			{
				//find nearest square for 100km
				$this->findNearby($square['x'], $square['y'], 100);
			}

		}
		else
		{
			$ok=false;
			
			//we don't have a square for given gridref, so first we
			//must figure out what the internal coords are for it
			
			$sql="select * from gridprefix where prefix='{$this->gridsquare}' limit 1";
			$prefix=$db->GetRow($sql);
			if (count($prefix))
			{
				$x=$prefix['origin_x'] + $this->eastings;
				$y=$prefix['origin_y'] + $this->northings;
			
				//what's the closes square with land? more than 5km away? disallow
				$ok=$this->findNearby($x,$y, 2, false);
			
				//check on the correct grid!;
				if ($ok && $this->nearest->reference_index != $prefix['reference_index'])
				{
					$ok = false;
				}
				
				unset($this->nearest);
				
				if ($ok)
				{
					//square is close to land, so we're letting it slide, but we
					//need to create the square - we give it a land_percent of -1
					//to indicate it needs review, and also to prevent it being
					//used in further findNearby calls
					$sql="insert into gridsquare(x,y,percent_land,grid_reference,reference_index,point_xy) 
						values($x,$y,-1,'$gridref',{$prefix['reference_index']},GeomFromText('POINT($x $y)') )";
					$db->Execute($sql);
					$gridimage_id=$db->Insert_ID();
					
					//ensure we initialise ourselves properly
					$this->loadFromId($gridimage_id);
				} else {
					//as we calculated it might as well return it in case useful...
					$this->x = $x;
					$this->y = $y;
				}
			
				//we know there are no images, so lets find some nearby squares...
				$this->findNearby($x, $y, 100);
			}
			
			
			if (!$ok) {
				$this->_error(sprintf($MESSAGES['class_gridsquare']['gridref_outside_area'], $gridref));
			}

		}

		
		return $ok;
	}
	
	/**
	* find a nearby occupied square and store it in $this->nearby
	* returns true if an occupied square was found
	* if occupied is false, finds the nearest land square
	*/
	function findNearby($x, $y, $radius, $occupied=true)
	{
		global $memcache;
		
		//fails quickly if not using memcached!
		$mkey = "$x,$y,$radius,$occupied";
		$nearest =& $memcache->name_get('gn',$mkey);
		if ($nearest) {
			$this->nearest = $nearest;
			return true;
		}
		
		$db=&$this->_getDB();

		//to optimise the query, we scan a square centred on the
		//the required point
		$left=$x-$radius;
		$right=$x+$radius;
		$top=$y-$radius;
		$bottom=$y+$radius;

		if ($occupied)
			$ofilter=" and imagecount>0 ";
		else
			$ofilter=" and percent_land>0 ";
		
		$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

		$sql="select *,
			power(x-$x,2)+power(y-$y,2) as distance
			from gridsquare where
			CONTAINS( 	
				GeomFromText($rectangle),
				point_xy)
			$ofilter
			order by distance asc limit 1";
		
		$square = $db->GetRow($sql);

		if (count($square) && ($distance = sqrt($square['distance'])) && ($distance <= $radius))
		{
			//round off distance
			$square['distance']=round($distance);
			
			//create new grid square and store members
			$this->nearest=new GridSquare;
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->nearest->$name=$value;
			}
			
			//fails quickly if not using memcached!
			$memcache->name_set('gn',$mkey,$this->nearest,$memcache->compress,$memcache->period_med);
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function findNearestPlace($radius,$gazetteer = '') {
		#require_once('geograph/gazetteer.class.php');
		
		if (!isset($this->nateastings))
			$this->getNatEastings();
			
		$gaz = new Gazetteer();
		
		return $gaz->findBySquare($this,$radius,null,$gazetteer);	
	}
	
	function &getImages($inc_all_user = false,$custom_where_sql = '',$order_and_limit = 'order by moderation_status+0 desc,seq_no')
	{
		global $memcache;
		
		//fails quickly if not using memcached!
		$mkey = md5("{$this->gridsquare_id}:$inc_all_user,$custom_where_sql,$order_and_limit");
		$images =& $memcache->name_get('gi',$mkey);
		if ($images) {
			return $images;
		}
		
		$db=&$this->_getDB();
		$images=array();
		if ($inc_all_user && ctype_digit($inc_all_user)) {
			$inc_all_user = "=$inc_all_user";
		}
		$i=0;
		$recordSet = &$db->Execute("select gi.*,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname ".
			"from gridimage gi ".
			"inner join user using(user_id) ".
			"where gridsquare_id={$this->gridsquare_id} $custom_where_sql ".
			"and (moderation_status in ('accepted', 'geograph') ".
			($inc_all_user?"or user.user_id $inc_all_user":'').") ".
			$order_and_limit);
		while (!$recordSet->EOF) 
		{
			$images[$i]=new GridImage;
			$images[$i]->fastInit($recordSet->fields);
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close(); 
		
		//fails quickly if not using memcached!
		$memcache->name_set('gi',$mkey,$images,$memcache->compress,$memcache->period_short);
		
		return $images;
	}
	
	function &getImageCount($inc_all_user = false,$custom_where_sql = '')
	{
		$db=&$this->_getDB();
		
		$count = $db->getOne("select count(*) 
			from gridimage gi 
			where gridsquare_id={$this->gridsquare_id} $custom_where_sql 
			and (moderation_status in ('accepted', 'geograph') ".
			($inc_all_user?"or gi.user_id = $inc_all_user":'').") ");
		
		return $count;
	}
	
	/**
	* Updates the imagecount and has_geographs columns for a square - use this after making changes
	*/
	function updateCounts()
	{
		$db=&$this->_getDB();
		
		//see if we have any geographs
			//we can use a limit, implied by GetOne (rather than count) beucase we only interested if *any* not now many, 'limit 1' will stop searching once found 1
		$geographs= $db->GetOne("select gridsquare_id from gridimage ".
					"where gridsquare_id={$this->gridsquare_id} and moderation_status='geograph'");

		$has_geographs=$geographs?1:0;

		//count how many images in the square
		$imagecount= $db->GetOne("select count(*) from gridimage ".
			"where gridsquare_id={$this->gridsquare_id} and moderation_status in ('accepted','geograph')");

		//update the has_geographs flag
		$db->Query("update gridsquare set has_geographs=$has_geographs,imagecount=$imagecount ".
			"where gridsquare_id={$this->gridsquare_id}");
	}
}


?>

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
   	* gridsquare.gridsquare_id primary key
   	*/
	var $gridsquare_id=0;

   /**
   	* gridsquare.grid_reference 
   	*/
	var $grid_reference='';
 
 	/**
	* gridsquare.reference_index type of grid reference
	*/
 	var $reference_index=0;
 
	/**
	* gridsquare.x,y internal grid position
	*/
 	var $x=0;
 	var $y=0;
 
 	/**
 	* gridsquare.percent_land how much land?
 	*/
  	var $percent_land=0;
  	
  	/**
	* gridsquare.percent_land how much land?
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
		if (!$this->db) die('Database connection failed');  
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
		if (!isset($this->nateastings)) {
			$db=&$this->_getDB();
			
			$square = $db->GetRow("select origin_x,origin_y from gridprefix where prefix=".$db->Quote($this->gridsquare));	
			
			//get the first gridprefix with the required reference_index
			//after ordering by x,y - you'll get the bottom
			//left gridprefix, and hence the origin
			
			$origin = $db->GetRow("select * from gridprefix where reference_index={$this->reference_index} order by origin_x,origin_y");	
			
			$square['origin_x'] -= $origin['origin_x'];
			$square['origin_y'] -= $origin['origin_y'];
			
			$this->nateastings = sprintf("%d%05d",intval($square['origin_x']/100),$this->eastings * 1000 + 500);
			$this->natnorthings = sprintf("%d%05d",intval($square['origin_y']/100),$this->northings * 1000 +500);
			
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
	function getGridPrefixes()
	{
		//only show gb grid if we have land there
		//show all irish grid squares...
		$db=&$this->_getDB();
		return $db->GetAssoc("select prefix,prefix from gridprefix ".
			"where landcount>0 ".
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
	* Stores the grid reference along with handy exploded elements 
	*/
	function _storeGridRef($gridref)
	{
		$this->grid_reference=$gridref;
		if (preg_match('/^([A-Z]{1,2})(\d\d)(\d\d)$/',$this->grid_reference, $matches))
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
		$ok=$ok && preg_match('/^[A-Z]{1,2}$/',$gridsquare);
		$ok=$ok && preg_match('/^[0-9]{1,2}$/',$eastings);
		$ok=$ok && preg_match('/^[0-9]{1,2}$/',$northings);
		return $ok;
	}

	/**
	* set up and validate grid square selection using seperate reference components
	*/
	function setGridPos($gridsquare, $eastings, $northings)
	{
		//assume the inputs are tainted..
		$ok=$this->validGridPos($gridsquare, $eastings, $northings);
		if ($ok)
		{
			$gridref=sprintf("%s%02d%02d", $gridsquare, $eastings, $northings);
			$ok=$this->_setGridRef($gridref);
		}
		
		return $ok;
	}

	/**
	* Just checks that a grid position is syntactically valid
	* No attempt is made to see if its a real grid position, just to ensure
	* that the input isn't anything nasty from the client side
	*/
	function validGridRef($gridref)
	{
		return preg_match('/^[A-Z]{1,2}[0-9]{4}$/',$gridref);
	}


	/**
	* set up and validate grid square selection using grid reference
	*/
	function setGridRef($gridref)
	{
		$gridref = preg_replace('/[^\w]+/','',strtoupper($gridref)); #assume the worse and remove everything, also not everyone uses the shift key
		//assume the inputs are tainted..
		$ok=$this->validGridRef($gridref);
		if ($ok)
		{
			$ok=$this->_setGridRef($gridref);
		}
		else
		{
			$this->_error(htmlentities($gridref).' is not a valid grid reference');
		}
		
		return $ok;
	}
	
	/**
	* load square from database
	*/
	function loadFromId($gridsquare_id)
	{
		$db=&$this->_getDB();
		$square = $db->GetRow("select * from gridsquare where gridsquare_id=".$db->Quote($gridsquare_id));	
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
			
			
		}
	}
	
	/**
	* set up and validate grid square selection
	*/
	function _setGridRef($gridref)
	{
		$ok=true;

		$db=&$this->_getDB();
		
		//store the reference 
		$this->_storeGridRef($gridref);
			
		//check the square exists in database
		$count=0;
		$square = $db->GetRow("select * from gridsquare where grid_reference=".$db->Quote($gridref));	
		if (count($square))
		{		
			//store cols as members
			foreach($square as $name=>$value)
			{
				if (!is_numeric($name))
					$this->$name=$value;
						
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
			//is it sea? what's the closes square with land? more than 5km away? disallow
			$ok=false;
			$this->_error("$gridref seems to be all at sea! Please contact us if you think this is in error");

		}

		
		return $ok;
	}
	
	/**
	* find a nearby occupied square and store it in $this->nearby
	* returns true if an occupied square was found
	*/
	function findNearby($x, $y, $radius)
	{
		$db=&$this->_getDB();
		
		//to optimise the query, we scan a square centred on the
		//the required point
		$left=$x-$radius;
		$right=$x+$radius;
		$top=$y-$radius;
		$bottom=$y+$radius;
		
		$sql="select *, ".
			"power(x-$x,2)+power(y-$y,2) as distance ".
			"from gridsquare where ".
			"x between $left and $right and ".
			"y between $top and $bottom and ".
			"imagecount>0 ".
			"order by distance asc limit 1";
		
		$square = $db->GetRow($sql);	
		$distance = sqrt($square['distance']);
		if (count($square) && ($distance <= $radius))
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
			
			return true;
		}
		else
		{
			return false;
		}
			
	}
	
	function &getImages()
	{
		$db=&$this->_getDB();
		$images=array();
		
		$i=0;
		$recordSet = &$db->Execute("select gridimage.*,user.realname,user.email,user.website ".
			"from gridimage ".
			"inner join user using(user_id) ".
			"where gridsquare_id={$this->gridsquare_id} ".
			"and moderation_status in ('pending', 'accepted', 'geograph')".
			"order by moderation_status+0 desc,seq_no");
		while (!$recordSet->EOF) 
		{
			$images[$i]=new GridImage;
			$images[$i]->loadFromRecordset($recordSet);
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close(); 
		
		return $images;
	}
}


?>
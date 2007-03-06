<?php

/**
 * $Project: GeoGraph $
 * $Id: functions.inc.php 2911 2007-01-11 17:37:55Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

/**************************************************
*
******/

class kmlPlacemark_Photo extends kmlPlacemark {



	public function addPhotographerPoint($kmlPoint,$view_direction=-1,$photographer = '') {
		
		//take a copy of the placemark
		$subjectPlacemark = clone $this;
		
		//make ourself a folder, and clear any options
		$this->tag = 'Folder';
		$this->items = array();
		$this->children = array();
		
		//set the minimum back
		$this->setItem('name',$subjectPlacemark->getItem('name'));
		if ($timestamp = $subjectPlacemark->getChild('TimeStamp')) {
			$this->addChild(clone $timestamp,'','TimeStamp');
		}
		if ($subjectPlacemark->issetItem('visibility')) {
			$this->setItem('visibility',$subjectPlacemark->getItem('visibility'));
		}
		
		//add the subject placemark
		$this->addChild($subjectPlacemark);
	
		//add the photographer placemark
		$photographerPlacemark = $this->addChild(new kmlPlacemark(null,'Photographer'));
		if (!empty($photographer)) {
			$photographerPlacemark->setItemCDATA('description',"($photographer)");
		}
		
		$sbjPoint = $subjectPlacemark->getChild('Point');
		
		//setup the sightline
		$MultiGeometry = $photographerPlacemark->addChild('MultiGeometry');
		$MultiGeometry->addChild($kmlPoint);
		$LineString = $MultiGeometry->addChild('LineString');
		$LineString->setItem('tessellate',1);
 		$LineString->setItem('altitudeMode','clampedToGround');
 		$LineString->setItem('coordinates',$sbjPoint->getItem('coordinates')." ".$kmlPoint->getItem('coordinates'));
 		
 		//seems a LookAt is required (but is nice to set the heading anyway!)
 		$LookAt = $photographerPlacemark->addChild('LookAt');
 		$this->addChild($LookAt);
 		$LookAt->setItem('longitude',$kmlPoint->lon);
		$LookAt->setItem('latitude',$kmlPoint->lat);
		$LookAt->setItem('tilt',70);
		$LookAt->setItem('range',1000);
		if (strlen($view_direction) && $view_direction != -1) {
			$LookAt->setItem('heading',$view_direction);
		} else {
			$LookAt->setItem('heading',$kmlPoint->calcHeadingToPoint($sbjPoint));
		}
		
		
		$Style = $photographerPlacemark->addChild('Style');
		$IconStyle = $Style->addChild('IconStyle');
		$IconStyle->setItem('scale',0.7);
		$Icon = $IconStyle->addChild('Icon');
		$Icon->setItem('href',"http://maps.google.com/mapfiles/kml/pal4/icon46.png");
		$LabelStyle = $Style->addChild('LabelStyle');
		$LabelStyle->setItem('scale',0);

		return $this;
	}
	
	public function addViewDirection($view_direction) {
		$LookAt = $this->addChild('LookAt');
		
		$sbjPoint = $this->getChild('Point');
		
		$LookAt->setItem('longitude',$sbjPoint->lon);
		$LookAt->setItem('latitude',$sbjPoint->lat);
		$LookAt->setItem('tilt',70);
		$LookAt->setItem('range',1000);
		if (strlen($view_direction) && $view_direction != -1) {
			$LookAt->setItem('heading',$view_direction);
		} else {
			$LookAt->setItem('heading',0);
		}
	}
}

class kmlPlacemark_Circle extends kmlPlacemark {
	public function __construct($id,$itemname = '',$kmlPoint = null,$d = 10000) {
		parent::__construct($id,$itemname,$kmlPoint);
		
		if (is_object($kmlPoint)) {
			$this->addCircle($kmlPoint,$d);
		}
	}

	public function addCircle($kmlPoint,$d) {
		if ($sbjPoint = $this->unsetChild('Point')) {
			$MultiGeometry = $this->addChild('MultiGeometry');
			$MultiGeometry->addChild($sbjPoint);
			$LineString = $MultiGeometry->addChild('LineString');
		} else {
			$LineString = $this->addChild('LineString');
		}
		$coordinates = array();
		
		// convert coordinates to radians
		$lat1 = deg2rad($kmlPoint->lat);
		$long1 = deg2rad($kmlPoint->lon);
		
		//d in meters;
		$d_rad = $d/6378137;
		
		// loop through the array and write path linestrings
		for($i=0; $i<360; $i+=12) {
			$radial = deg2rad($i);
			$lat_rad = asin(sin($lat1)*cos($d_rad) + cos($lat1)*sin($d_rad)*cos($radial));
			$dlon_rad = atan2(sin($radial)*sin($d_rad)*cos($lat1), cos($d_rad)-sin($lat1)*sin($lat_rad));
			$lon_rad = fmod(($long1+$dlon_rad + M_PI), 2*M_PI) - M_PI;
			$coordinates[] = sprintf('%.6f,%.6f,0',rad2deg($lon_rad),rad2deg($lat_rad));
		}
		$coordinates[] = $coordinates[0];//join it back up by reusing the first point

		$LineString->setItem('coordinates',join(' ',$coordinates));
	}
	
}




function getKmlFilepath($extension,$level,$square = null,$gr='') {
	if (is_object($square)) {
		$s = $square->gridsquare;
		if ($level > 2) {
			$n = sprintf("%d%d",intval($square->eastings/20)*2,intval($square->northings/20)*2);
		}
		
	} elseif (!empty($gr)) {
		preg_match('/^([A-Z]{1,2})([\d_]*)$/',strtoupper($gr),$m);
		$s = $m[1];
		if ($level > 2) {
			$numbers = $m[2];
			$numlen = strlen($m[2]);
			$c = $numlen/2;
			
			$n = sprintf("%d%d",intval($numbers{0}/2)*2,intval($numbers{$c}/2)*2);
		}
	}
	
	$base=$_SERVER['DOCUMENT_ROOT'].'/kml';
	if (!is_dir("$base/$s"))
		mkdir("$base/$s");
	if ($n && !is_dir("$base/$s/$n"))
		mkdir("$base/$s/$n");
	
	if ($level == 3) {
		return "/kml/$s/$n.$extension";
	} elseif ($level == 2) {
		return "/kml/$s.$extension";
	} elseif ($level == 1) {
		return '/kml/geograph.'.$extension;
	} else {
		if ($n && !is_dir("$base/$s/$n/$level"))
			mkdir("$base/$s/$n/$level");
		return "/kml/$s/$n/$level/$gr.$extension";
	}

}

function kmlPageFooter(&$kml,&$square,$gr,$self,$level) {
	global $db;
	
	if (isset($_GET['debug'])) {
		print "<a href=?download>Open in Google Earth</a><br/>";
		print "<textarea rows=35 style=width:100%>";
		print $kml->returnKML();
		print "</textarea>";
	} elseif (isset($_GET['download'])) {
		$kml->outputKML();
		exit;
	} else {
		$file = getKmlFilepath($kml->extension,$level,$square,$gr);
		
		$db->Execute("replace into kmlcache set `url` = '$self?gr=$gr',filename='$file',`level` = $level,`rendered` = 1");

		$base=$_SERVER['DOCUMENT_ROOT'];
		$kml->outputFile('kmz',false,$base.$file);
	}
}


?>
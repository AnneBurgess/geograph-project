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

class kmlPrimative {

	public $tag = '';
	public $id = '';

	public $items = array();

	public $children = array();

	public function __construct($tag,$id = '') {
		$this->tag = $tag;
		if (!empty($id)) {
			$this->id = $id;
		}
	}

	public function setItem($name,$value) {
		$this->items[$name] = $value;
		return $this;
	}

	public function setItemCDATA($name,$value) {
		$this->items[$name] = "<![CDATA[$value]]>";
		return $this;
	}

	public function getItem($name) {
		return $this->items[$name];
	}

	public function issetItem($name) {
		return isset($this->items[$name]);
	}

	public function addChild($obj = null,$id = '',$ref = '') {
		if (!is_object($obj)) {
			if (class_exists('kml'.$obj,false)) {
				$classname = 'kml'.$obj;
				$obj = new $classname($id);
			} else {
				$obj = new kmlPrimative($obj,$id);
			}
		}
		if (!empty($ref)) {
			$this->children[$ref] = $obj;
		} else {
			$this->children[] = $obj;
		}
		return $obj;
	}

	public function getChild($ref) {
		return $this->children[$ref];
	}

	public function unsetChild($ref) {
		if (isset($this->children[$ref])) {
			$d = $this->children[$ref];
			unset($this->children[$ref]);
			return $d;
		}
	}

	public function setTimeStamp($when) {
		$this->addChild('TimeStamp','','TimeStamp')->setItem('when',$when);
		return $this;
	}

	public function toString($indent = 0) {
		$s = str_repeat("\t",$indent)."<{$this->tag}";
		if (!empty($this->id)) {
			$s .= " id=\"{$this->id}\"";
		}
		$s .= ">\n";
		if (count($this->items)) {
			foreach ($this->items as $name => $value) {
				$s .= str_repeat("\t",$indent+1)."<$name>$value</$name>\n";
			}
		}
		if (count($this->children)) {
			foreach ($this->children as $id => $obj) {
				$s .= $obj->toString($indent+1);
			}
		}
		return $s.str_repeat("\t",$indent)."</{$this->tag}>\n";
	}

}

/**************************************************
*
******/

class kmlFile extends kmlPrimative {
	var $extension = 'kmz';
	
	public function __construct($id = '') {
		$this->contentType = "application/vnd.google-earth.kml+xml";
		$this->encoding = "utf-8";
		parent::__construct('kml',$id);
	}
	
	public function outputFile($extension='',$sendheaders = true,$diskfile = '') {
		if (!empty($extension)) {
			$this->extension = $extension;
		}
		if ($this->extension == 'kml') {
			return $this->outputKML($sendheaders,$diskfile);
		} else {
			return $this->outputKMZ($sendheaders,$diskfile);
		}
	}

	
	public function outputKML($sendheaders = true,$diskfile = '') {
		if (empty($this->id)) {
			$this->id = uniqid();
		}
		if (empty($this->filename)) {
			$this->filename = $this->id.".kml";
		}
		if ($sendheaders && !headers_sent()) {
			Header("Content-Type: ".$this->contentType."; charset=".$this->encoding."; filename=".basename($this->filename));
			Header("Content-Disposition: attachment; filename=".basename($this->filename));
		}
		if (empty($diskfile)) {
			echo $this->returnKML();
		} else {
			file_put_contents($diskfile,$this->returnKML());
		}
		return $this->filename;
	}

	function outputKMZ($sendheaders = true,$diskfile = '') {
		$this->contentType = "application/vnd.google-earth.kmz+xml";

		if (empty($this->id)) {
			$this->id = uniqid();
		}
		if (empty($this->filename)) {
			$this->filename = $this->id.".kmz";
		}
		if ($sendheaders && !headers_sent()) {
			Header("Content-Type: ".$this->contentType."; charset=".$this->encoding."; filename=".basename($this->filename));
			Header("Content-Disposition: attachment; filename=".basename($this->filename));
		}

		$content = $this->returnKML();

		include("zip.class.php");
		
		$zipfile = new zipfile();   
		
		// add the binary data stored in the string 'content' 
		$zipfile -> addFile($content, "doc.kml");   
		
		if (empty($diskfile)) {
			echo $zipfile->file();
		} else {
			file_put_contents($diskfile,$zipfile->file());
		}
		
		return $this->filename;
	}

	public function returnKML() {
		if (empty($this->id)) {
			$this->id = uniqid();
		}

		$s = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";

		$this->id .= "\" xmlns=\"http://earth.google.com/kml/2.0";

		return $s.parent::toString();
	}

}

/**************************************************
*
******/

class kmlDocument extends kmlPrimative {

	public function __construct($id = '') {
		parent::__construct('Document',$id);
	}

	public function addHoverStyle($id='def',$iconScale = 1,$hoverScale=2.1,$icons='') {
		if ($icons) {
			switch ($icons) {
				case 'photo': $normal = 'pal4/icon46.png';$hover = 'pal4/icon38.png'; break;
				case 'multi': $normal = 'pal3/icon40.png';$hover = 'pal3/icon32.png'; break;
			}
		}


		$Style = $this->addChild('Style','n'.$id);
		$Style->addChild('LabelStyle')->setItem('scale',0);
		$IconStyle = $Style->addChild('IconStyle');
		if ($iconScale != 1)
			$IconStyle->setItem('scale',$iconScale);
		if ($normal)
			$IconStyle->addChild('Icon')->setItem('href','http://maps.google.com/mapfiles/kml/'.$normal);


		$Style2 = $this->addChild('Style','h'.$id);
		$IconStyle2 = $Style2->addChild('IconStyle');
		if ($iconScale != 1 || $hoverScale) 
			$IconStyle2->setItem('scale',$hoverScale*$iconScale);
		if ($hover)
			$IconStyle2->addChild('Icon')->setItem('href','http://maps.google.com/mapfiles/kml/'.$hover);


		$StyleMap = $this->addChild('StyleMap',$id);
		$Pair = $StyleMap->addChild('Pair');
		$Pair->setItem('key','normal');
		$Pair->setItem('styleUrl','#n'.$id);

		$Pair2 = $StyleMap->addChild('Pair');
		$Pair2->setItem('key','highlight');
		$Pair2->setItem('styleUrl','#h'.$id);

		return $this;
	}
}

class kmlFolder extends kmlDocument {
	public function __construct($id = '') {
		parent::__construct($id);
		$this->tag = 'Folder';
	}
}

/**************************************************
*
******/

class kmlPlacemark extends kmlPrimative {

	public function __construct($id,$itemname = '',$kmlPoint = null) {
		parent::__construct('Placemark',$id);
		if (!empty($itemname)) {
			$this->setItemCDATA('name',$itemname);
		}
		if (is_object($kmlPoint)) {
			$this->addChild($kmlPoint,$id,'Point');
		}
	}

	public function useHoverStyle() {
		$this->setItem('styleUrl','#def');
		return $this;
	}

	public function usePhotoStyle($prefix='') {
		$this->setItem('styleUrl','#pho'.$prefix);
		return $this;
	}

	public function useImageAsIcon($url) {
		$Style = $this->addChild('Style');
		$IconStyle = $Style->addChild('IconStyle');
		$Icon = $IconStyle->addChild('Icon');
		$Icon->setItem('href',htmlspecialchars($url));
		return $this;
	}

	public function makeFloating() {
		$this->getChild('Point')->makeFloating();
		return $this;
	}
}

/**************************************************
*
******/

class kmlNetworkLink extends kmlPrimative {

	public function __construct($id,$itemname = '',$url = null) {
		parent::__construct('NetworkLink',$id);
		if (!empty($itemname)) {
			$this->setItem('name',$itemname);
		}
		if (!empty($url)) {
			$this->useUrl($url);
		}
	}


	public function useUrl($url) {
		$Url = $this->addChild('Url','','Url');
		$Url->setItem('href',htmlspecialchars($url));
		return $Url;
	}
}

/**************************************************
*
******/

class kmlPoint extends kmlPrimative {

	public function __construct($lat=0,$lon=0,$alt=0) {
		parent::__construct('Point');
		$this->setItem('coordinates',"$lon,$lat,$alt");
		$this->lat = $lat;
		$this->lon = $lon;
		$this->alt = $alt;
	}

 	public function makeFloating() {
 		$point = $this->getChild('Point');
 		$this->setItem('extrude',1);
 		$this->setItem('altitudeMode','relativeToGround');
 		$this->alt = 125;
 		return $this;
	}

	public function toString($indent = 0) {
		//make sure coordinates are uptodate
		$this->setItem('coordinates',"{$this->lon},{$this->lat},{$this->alt}");
		return parent::toString($indent);
	}

	function calcHeadingToPoint($p2) {
		$p2lon = deg2rad($p2->lon);
		$p2lat = deg2rad($p2->lat);
		$p1lon = deg2rad($this->lon);
		$p1lat = deg2rad($this->lat);

		$y = sin($p2lon-$p1lon) * cos($p2lat);
		$x = cos($p1lat)*sin($p2lat) - sin($p1lat)*cos($p2lat)*cos($p2lon-$p1lon);
		return rad2deg(atan2($y, $x));
	}
}


/**************************************************
*
******/

class kmlRegion extends kmlPrimative {

	public function __construct() {
		parent::__construct('Region');
	}

	public function setBoundary($north,$south,$east,$west) {
		$LatLonAltBox = $this->addChild('LatLonAltBox','','LatLonAltBox');
		$LatLonAltBox->setItem('north',$north);
		$LatLonAltBox->setItem('south',$south);
		$LatLonAltBox->setItem('east',$east);
		$LatLonAltBox->setItem('west',$west);
	}

	public function setPoint($kmlPoint,$d = 0.0001) {
		$LatLonAltBox = $this->addChild('LatLonAltBox','','LatLonAltBox');
		$LatLonAltBox->setItem('north',$kmlPoint->lat+$d);
		$LatLonAltBox->setItem('south',$kmlPoint->lat-$d);
		$LatLonAltBox->setItem('east',$kmlPoint->lon+$d);
		$LatLonAltBox->setItem('west',$kmlPoint->lon-$d);
	}

	public function setLod($min,$max) {
		$Lod = $this->addChild('Lod','','Lod');
		$Lod->setItem('minLodPixels',$min);
		$Lod->setItem('maxLodPixels',$max);
	}

	public function setFadeExtent($min,$max) {
		$Lod = $this->getChild('Lod');
		$Lod->setItem('minFadeExtent',$min);
		$Lod->setItem('maxFadeExtent',$max);
	}
}

?>
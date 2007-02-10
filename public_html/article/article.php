<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($_GET['page']) || preg_match('/[^\w-\.]/',$_GET['page'])) {
	$smarty->display('static_404.tpl');
	exit;
}

$isadmin=$USER->hasPerm('moderator')?1:0;

$template = 'article_article.tpl';
$cacheid = $_GET['page'];
$cacheid .= "|".$USER->hasPerm('moderator')?1:0;
$cacheid .= "--".(isset($_SESSION['article_urls']) && in_array($_GET['page'],$_SESSION['article_urls'])?1:0);


function article_make_table($input) {
	$rows = explode("\n",$input);
	$output = "<table class=\"report\">";
	$c = 1;
	foreach ($rows as $row) {
		$head = 0;
		if (strpos($row,'*') === 0) {
			$row = preg_replace('/^\*/','',$row);
			$output .= "<thead>";
			$head = 1;
		} elseif ($c ==1) {
			$output .= "<tbody>";
		}
		$output .= "<tr>";
		
		$row = preg_replace('/^\| | \|$/','',$row);
		$cells = explode(' | ',$row);
		
		foreach ($cells as $cell) {
			$output .= "<td>$cell</td>";
		}
		
		$output .= "</tr>";
		if ($head) {
			$output .= "</thead>";
			$output .= "<tbody>";
		}
		$c++;
	}
	return $output."</tbody></table>";
}

function smarty_function_articletext($input) {
	$output = preg_replace('/(-{7,})\n(.*?)(-{7,})/es',"article_make_table('\$2')",str_replace("\r",'',$input));

	$output = str_replace(
		array('[b]','[/b]','[big]','[/big]','[i]','[/i]','[h2]','[/h2]','[h3]','[/h3]','[h4]','[/h4]','[float]','[/float]','[br/]'),
		array('<b>','</b>','<big>','</big>','<i>','</i>','<h2>','</h2>','<h3>','</h3>','<h4>','</h4>','<div style="float:left">','</div>','<br style="clear:both"/>'),
		$output);

	$pattern=array(); $replacement=array();

	$pattern[]='/\{image id=(\d+) text=([^\}]+)\}/e';
	$replacement[]="smarty_function_gridimage(array(id => '\$1',extra => '\$2'))";

	$pattern[]="/\[url[=]?\](.+?)\[\/url\]/i";
	$replacement[]='\1';

	$pattern[]="/\[url=((f|ht)tp[s]?:\/\/[^<> \n]+?)\](.+?)\[\/url\]/ie";
	$replacement[]="smarty_function_external(array('href'=>\"\$1\",'text'=>'\$3','title'=>\"\$1\"))";

	$pattern[]='/(?<!["\'\[\/\!\w])([STNH]?[A-Z]{1}\d{4,10})(?!["\'\]\/\!\w])/';
	$replacement[]="<a href=\"http://{$_SERVER['HTTP_HOST']}/gridref/\\1\" target=\"_blank\">\\1</a>";

	$pattern[]='/\[img=([^\] ]+)(| [^\]]+)\]/';
	$replacement[]='<img src="\1" alt="\2" title="\2"/>';

	$pattern[]='/\n\* ?([^\n]+)(\n{2})?/e';
	$replacement[]="'<ul style=\"margin-bottom:0px;margin-top:0px\"><li>\$1</li></ul>'.('$2'?'\n':'')";
	$pattern[]='/<\/ul>\n?<ul style=\"margin-bottom:0px;margin-top:0px\">/';
	$replacement[]='';

	//fix a bug where double spacing on a previous match would swallow the newline needed for the next
	$pattern[]='/\n\n(<\w{1,3}>)\#/';
	$replacement[]="\n\$1#";
	
	$pattern[]='/\n\n\#/';
	$replacement[]="\n\r\n\$1#";
	
	$pattern[]='/\n(<\w{1,3}>)?\#([\w]{1,2})? ([^\n]+)(<\/\w{1,3}>)?(\n{2})?/e';
	$replacement[]="'<ol style=\"margin-bottom:0px;'.('\$1'?'':'margin-top:0px').'\"'.('\$2'?' start=\"\$2\"':'').'><li>\$1\$3\$4</li></ol>'.('\$5'?'\n':'')";
	$pattern[]='/<\/ol>\n?<ol style=\"margin-bottom:0px;margin-top:0px\">/';
	$replacement[]='';

	$pattern[]="/\n/";
	$replacement[]="<br/>\n";

	if (preg_match_all('/\[smallmap ([STNH]?[A-Z]{1}\d{4,10})\]/',$output,$m)) {
		foreach ($m[0] as $i => $full) {
			//lets add an rastermap too
			$square = new Gridsquare;
			$square->setByFullGridRef($m[1][$i],true);
			$square->grid_reference_full = 	$m[1][$i];

			$rastermap = new RasterMap($square,false);
			if ($rastermap->service == 'OS50k') {
				$rastermap->service = 'OS50k-small';
				$rastermap->width = 125;
				
				$pattern[] = "/".preg_quote($full)."/";
				$replacement[] = $rastermap->getImageTag();
				
			}
		}
	}
	
	$output=preg_replace($pattern, $replacement, $output);
	
	if (count($m)) {
		$output .= '<div class="copyright">Great Britain 1:50 000 Scale Colour Raster Mapping Extracts &copy; Crown copyright Ordnance Survey. All Rights Reserved. Educational licence 100045616.</div>';
	}
	
	return GeographLinks($output,true);
}

$smarty->register_modifier("articletext", "smarty_function_articletext");

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);

	$page = $db->getRow("
	select article.*,realname,gs.grid_reference
	from article 
		left join user using (user_id)
		left join gridsquare gs on (article.gridsquare_id = gs.gridsquare_id)
	where ( (licence != 'none' and approved = 1) 
		or user.user_id = {$USER->user_id}
		or $isadmin )
		and url = ".$db->Quote($_GET['page']).'
	limit 1');
	if (count($page)) {
		foreach ($page as $key => $value) {
			$smarty->assign($key, $value);
		}
	} else {
		$template = 'static_404.tpl';
	}
}




$smarty->display($template, $cacheid);

	
?>

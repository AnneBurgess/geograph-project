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
include_messages('ecard');

init_session();

$smarty = new GeographPage;
$template='ecard.tpl';	

//you must be logged in to send e-cards
$USER->mustHavePerm("basic");

//gather what we need	
$from_name=isset($_POST['from_name'])?stripslashes($_POST['from_name']):$USER->realname;
$from_email=isset($_POST['from_email'])?stripslashes($_POST['from_email']):$USER->email;

$to_name=isset($_POST['to_name'])?stripslashes($_POST['to_name']):'';
$to_email=isset($_POST['to_email'])?stripslashes($_POST['to_email']):'';

$smarty->assign_by_ref('from_name', $from_name);
$smarty->assign_by_ref('from_email', $from_email);

$smarty->assign_by_ref('to_name', $to_name);
$smarty->assign_by_ref('to_email', $to_email);

$db=NewADOConnection($GLOBALS['DSN']);
if (empty($db)) die('Database connection failed');

if ($db->getOne("select count(*) from throttle where used > date_sub(now(), interval 1 hour) and user_id={$USER->user_id} AND feature = 'ecard'") > 8) {
	$smarty->assign('throttle',1);
	$throttle = 1;
} elseif ($db->getOne("select count(*) from throttle where used > date_sub(now(), interval 24 hour) and user_id={$USER->user_id} AND feature = 'ecard'") > 30) {
	$smarty->assign('throttle',1);
	$throttle = 1;
} else {
	$throttle = 0;
}

if (rand(1,10) > 5) {
	$db->query("delete from throttle where used < date_sub(now(), interval 48 hour)");
}


if (isset($_REQUEST['image']))
{
	//initialise message
	require_once('geograph/gridsquare.class.php');
    require_once('geograph/gridimage.class.php');

	$image=new GridImage();
	$image->loadFromId($_REQUEST['image']);
	
	if ($image->moderation_status=='rejected' || $image->moderation_status=='pending') {
		//clear the image
		$image=new GridImage;
	} else {
		$msg = $MESSAGES['ecard']['message_template'];

		$smarty->assign_by_ref('msg', $msg);
	}
	$smarty->assign_by_ref('image', $image);
}
	
//try and send?
if (!$throttle && isset($_POST['msg']))
{
	$ok=true;
	$msg=htmlentities(trim(stripslashes($_POST['msg'])));
	
	$errors=array();
	if (!isValidEmailAddress($from_email))
	{
		$ok=false;
		$errors['from_email']=$MESSAGES['ecard']['email_invalid'];
	}
	if (!isValidRealName($from_name))
	{
		$ok=false;
		$errors['from_name']=$MESSAGES['ecard']['name_chars'];
	}
	if (!isValidEmailAddress($to_email))
	{
		$ok=false;
		$errors['to_email']=$MESSAGES['ecard']['email_invalid'];
	}
	if (!isValidRealName($to_name))
	{
		$ok=false;
		$errors['to_name']=$MESSAGES['ecard']['name_chars'];
	}
	if (strlen($msg)==0)
	{
		$ok=false;
		$errors['msg']=$MESSAGES['ecard']['empty_message'];
	}
	$smarty->assign_by_ref('errors', $errors);

	$smarty->assign_by_ref('msg', html_entity_decode($msg)); //will be re-htmlentities'ed when output
	$smarty->assign_by_ref('charset', $CONF['mail_charset']);
	$smarty->assign_by_ref('contactmail', $CONF['abuse_email']);

	$enc_from_name = mb_encode_mimeheader($from_name, $CONF['mail_charset'], $CONF['mail_transferencoding']);
	$enc_to_name = mb_encode_mimeheader($to_name, $CONF['mail_charset'], $CONF['mail_transferencoding']);

	//still ok?
	if ($ok && !isset($_POST['edit']))  
	{
		//build message and send it...
		
		
		$smarty->assign_by_ref('htmlmsg', nl2br($msg));
		
		$body=$smarty->fetch('email_ecard.tpl');
		$subject=sprintf($MESSAGES['ecard']['mail_subject'], $from_name);
		$encsubject=mb_encode_mimeheader($CONF['mail_subjectprefix'].$subject, $CONF['mail_charset'], $CONF['mail_transferencoding']);
		
		if (isset($_POST['preview'])) {
			preg_match_all('/(<!DOCTYPE.*<\/HTML>)/s',$body,$matches);
	
			print $MESSAGES['ecard']['preview_title'];
			print "<form method=\"post\">";
			foreach ($_POST as $name => $value) {
				if ($name != 'preview') {
					print "<input type=\"hidden\" name=\"$name\" value=\"".htmlentities($value)."\">";
				}
			}
			printf($MESSAGES['ecard']['preview_html'], $to_email, $subject);
			$html = preg_replace("/=[\n\r]+/s","\n",$matches[1][0]);
			$html = preg_replace("/=(\w{2})/e",'chr(hexdec("$1"))',$html);
			print $html;
			exit;
		} else {
			$db->query("insert into throttle set user_id={$USER->user_id},feature = 'ecard'");
			
			$ip=getRemoteIP();
			
			$headers = array();
			$headers[] = "From: $enc_from_name <{$USER->email}>";
			if ($from_email != $USER->email) 
				$headers[] = "Reply-To: $enc_from_name <$from_email>";
			$headers[] = "X-GeographUserId:{$USER->user_id}";
			$headers[] = "X-IP:$ip";

			$headers[] = "Content-Type: multipart/alternative;\n	boundary=\"----=_NextPart_000_00DF_01C5EB66.9313FF40\"";
			
			$hostname=trim(`hostname --fqdn`);
			$received="Received: from [{$ip}]".
				" by {$hostname} ".
				"with HTTP;".
				strftime("%d %b %Y %H:%M:%S -0000", time())."\n";

			$envfrom = is_null($CONF['mail_envelopefrom'])?null:"-f {$CONF['mail_envelopefrom']}";

			@mail("$enc_to_name <$to_email>", $encsubject, $body, $received.implode("\n",$headers), $envfrom);

			$smarty->assign('sent', 1);
		}
	}
}


$smarty->display($template);

	
?>

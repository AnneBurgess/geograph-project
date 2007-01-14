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
* Provides the GeographUser class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/


/**
* Geograph User class
*
* Provides facilities for inline login and querying permissions
* of current user (which might be an anonymous)
*
* @package Geograph
*/


require_once('geograph/gridsquare.class.php');


class GeographUser
{
	/**
	* current user_id, 0 for guest user
	*/
	var $user_id=0;
	
	/**
	* registered user?
	*/
	var $registered=false;
	
	/**
	* records whether user was automatically logged in via cookie - 
	* there are some operations which should force the user to give
	* their password for additional security in this event
	*/
	var $autologin=false;
	
	/**
	* stats gathered by getStats
	*/
	var $stats=array();

	
	/**
	* Constructor doesn't normally do anything, but if supplied with a user id
	* can be used to create an instance for a particular user. 
	*/
	function GeographUser($uid=0)
	{
		if (($uid>0) && preg_match('/^[0-9]+$/' , $uid))
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed');   
			
			
						
			$arr =& $db->GetRow("select * from user where user_id=$uid limit 1");	
			if (count($arr))
			{
				$this->registered=strlen($arr['rights'])>0;
				foreach($arr as $name=>$value)
				{
					if (!is_numeric($name))
						$this->$name=$value;

				}

				// get user homesquare
				if (isset($this->home_gridsquare)) {
					$gs = new GridSquare();
					$gs->loadFromId($this->home_gridsquare);
					$this->grid_reference = $gs->grid_reference;
				}

			}
		}
	}
	
	function loadByNickname($nickname=0)
	{
		if (!empty($nickname))
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed');   

			$nickname = $db->Quote($nickname);
			
			$arr =& $db->GetRow("select * from user where nickname = $nickname limit 1");
			
			
			//todo check seperate table
			
			if (count($arr))
			{
				$this->registered=strlen($arr['rights'])>0;
				foreach($arr as $name=>$value)
				{
					if (!is_numeric($name))
						$this->$name=$value;

				}

				// get user homesquare
				if (isset($this->home_gridsquare)) {
					$gs = new GridSquare();
					$gs->loadFromId($this->home_gridsquare);
					$this->grid_reference = $gs->grid_reference;
				}
			}
		}
	}
	
	
	function getForumSortOrder() {
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  
	
		$this->sortBy =& $db->getOne("select user_sorttopics from geobb_users where user_id='{$this->user_id}'");
		return $this->sortBy;
	}
	
	function setDefaultStyle($style) {
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  

		$db->Execute("update user set default_style = '$style' where user_id='{$this->user_id}'");
		$this->default_style = $style;
	}
	
	function getStyle($style='white') {
		$valid_style=array('white', 'black','gray');
		if (isset($_GET['style']) && in_array($_GET['style'], $valid_style))
		{
			$style=$_GET['style'];
			$_SESSION['style']=$style;

			if ($this->registered) 
				$this->setDefaultStyle($style);
		}
		elseif ($this->registered && in_array($this->default_style, $valid_style)) 
		{
			$style=$this->default_style;
		}
		elseif (isset($_SESSION['style']))
		{
			$style=$_SESSION['style'];
		}
		return $style;
	}
	
	
	/**
	* get stats for user represented by this instance - 
	* all stats are stored in
	*/
	function getStats()
	{
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');   
		
		$this->stats=array();
		

		$this->stats['total']=$db->GetOne("select count(*) from gridimage where user_id='{$this->user_id}' and moderation_status<>'rejected'");
		$this->stats['pending']=$db->GetOne("select count(*) from gridimage where user_id='{$this->user_id}' and moderation_status='pending'");
		$this->stats['squares']=$db->GetOne("select count(distinct grid_reference) from gridimage_search where user_id='{$this->user_id}'");
		
		$this->stats += $db->GetRow("select sum(ftf=1) as ftf,count(distinct grid_reference) as geosquares from gridimage_search where user_id='{$this->user_id}' and moderation_status='geograph'");

	}
	
	/**
	* register user 
	* returns true if successful and false if not. Array of
	* errors returned via $error param
	*/
	function register(&$form, &$errors)
	{
		global $CONF;
		
		//get the inputs
		$name=stripslashes(trim($form['name']));
		$email=stripslashes(trim($form['email']));
		$password1=stripslashes(trim($form['password1']));
		$password2=stripslashes(trim($form['password2']));
		
		//check the registration
		$ok=true;
		
		$errors=array();
		
		//check name
		if (strlen($name)==0)
		{
			$ok=false;
			$errors['name']='You must give your name';
		}
		else
		{
			if (!isValidRealName($name))
			{
				$ok=false;
				$errors['name']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
			}
		}
		
		//basic email address check
		if (!isValidEmailAddress($email))
		{
			$ok=false;
			$errors['email']='Please enter a valid email address';
		}
		
		//check password
		if (strlen($password1)==0)
		{
			$ok=false;
			$errors['password1']='You must specify a password';
		}
		elseif ($password1!=$password2)
		{
			$ok=false;
			$errors['password2']='Passwords didn\'t match, please try again';
		}
		
		//if the params check out, lets ensure they aren't 
		//already registered...
		if ($ok)
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed');   

			# no need to call connect/pconnect!
			$arr = $db->GetRow('select * from user where email='.$db->Quote($email).' and rights is not null limit 1');	
			if (count($arr))
			{
				//email address already exists in database
				$ok=false;
				$errors['email']='Email address is already registered';
			}
			else
			{
				//we know there is no confirmed user with email address, so if we have
				//an unconfirmed one, we can overwrite it with the new details
				$arr = $db->GetRow('select * from user where email='.$db->Quote($email).' and rights is null limit 1');	
				if (count($arr))
				{
					//user already exists, but didn't respond to email - probably trying
					//to send a fresh one so lets just refresh the existing record
					$user_id=$arr['user_id'];	
					
					$sql = sprintf("update user set realname=%s,email=%s,password=%s,signup_date=now() where user_id=$user_id",
						$db->Quote($name),
						$db->Quote($email),
						$db->Quote($password1),
						$db->Quote($user_id));
						
					if ($db->Execute($sql) === false) 
					{
						$errors['general']='error updating: '.$db->ErrorMsg();
						$ok=false;
					}
				
				}
				else
				{
					//ok, user doesn't exist, insert a new row
					$sql = sprintf("insert into user (realname,email,password,signup_date) ".
						"values (%s,%s,%s,now())",
						$db->Quote($name),
						$db->Quote($email),
						$db->Quote($password1));
				
					
					if ($db->Execute($sql) === false) 
					{
						$errors['general']='error inserting: '.$db->ErrorMsg();
						$ok=false;
					}
					else
					{
						$user_id=$db->Insert_ID();	
					}
				}
				
				if ($ok)
				{
					//put the user_id into this user object
					$this->user_id=$user_id;
					
					//build an authentication url
					$register_authentication_url="http://".
						$_SERVER['HTTP_HOST'].'/reg/'.$user_id.
						'/'.substr(md5($user_id.$CONF['register_confirmation_secret']),0,16);
					
					$msg="Thankyou for registering at http://".$_SERVER['HTTP_HOST']."\n\n";
					
					$msg="Before you can log in, you must first confirm your registration ".
						"by following the link below:\n\n";
					$msg.=$register_authentication_url."\n\n";
					
					$msg.="Once you have confirmed your registration, you will be able to ".
						"log in with the email address and password you provided:\n";
					$msg.="    email: $email\n";
					$msg.="    password: $password1\n\n";
					
					$msg.="We hope you enjoy using and contributing to the site\n\n";
					$msg.="Kind Regards,\n\n";
					$msg.="The Geograph.org.uk Team";
					
					
					@mail($email, '[geograph] Confirm registration', $msg,
						"From: Geograph Website <noreply@geograph.org.uk>");
				
				}
			}
		}
		
		return $ok;
	}

	/**
	* verify registration from given hash
	* can only do this once, returns ok, fail or alreadycomplete
	*/
	function verifyRegistration($user_id, $hash)
	{
		global $CONF;
		$ok=true;
		$status="ok";
		
		//validate inputs, they came from outside
		$ok=$ok && preg_match('/\d+/', $user_id);
		$ok=$ok && preg_match('/[0-9a-f]+/', $hash);
		
		//validate hash
		$ok=$ok && ($hash==substr(md5($user_id.$CONF['register_confirmation_secret']),0,16));
		if ($ok)
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			
			
			$arr = $db->GetRow('select * from user where user_id='.$db->Quote($user_id).' limit 1');	
			if (strlen($arr['rights']))
			{
				$status="alreadycomplete";
			
			}
			else
			{
			
				//assign some basic rights to the user
				$sql="update user set rights='basic' where user_id=".$db->Quote($user_id);
				$db->Execute($sql);

				$this->user_id=$user_id;
				$this->registered=true;

				$arr = $db->GetRow('select * from user where user_id='.$db->Quote($user_id).' limit 1');	
				foreach($arr as $name=>$value)
				{
					if (!is_numeric($name))
						$this->$name=$value;

				}

				//temporary nickname fix for beta accounts
				if (strlen($this->nickname)==0)
					$this->nickname=str_replace(" ", "", $this->realname);


				//setup forum user
				$this->_forumUpdateProfile();

				//log into forum too
				$this->_forumLogin();
				
				$status="ok";
			}
				
		}
		else
		{
			//hash mismatch or param problem
			$status="fail";
		}
		
		return $status;
	}
	
	/**
	* send password reminder to email address
	*/
	function sendReminder($email, &$errors)
	{
		$errors=array();
		$ok=false;
		
		if (isValidEmailAddress($email))
		{
			$db = NewADOConnection($GLOBALS['DSN']);

			//user registered?
			$arr = $db->GetRow('select * from user where email='.$db->Quote($email).' limit 1');	
			if (count($arr))
			{
				$msg="Someone, probably you, requested a password reminder for ".$_SERVER['HTTP_HOST']."\n\n";
				$msg.="Your password is: ".$arr['password']."\n\n";

				@mail($email, 'Password Reminder for '.$_SERVER['HTTP_HOST'], $msg,
				"From: Geograph Website <noreply@geograph.org.uk>");

				$ok=true;
			}
			else
			{
				$errors['email']="This email address isn't registered";
			}
		}
		else
		{
			$errors['email']='This isn\'t a valid email address';
		}
		
		return $ok;
	}
	
	/**
	* verify registration from given hash
	* can only do this once, returns ok, fail or alreadycomplete
	*/
	function verifyEmailChange($change_id, $hash)
	{
		global $CONF;
		$ok=true;
		$status="ok";
		
		//validate inputs, they came from outside
		$ok=$ok && preg_match('/m\d+/', $change_id);
		$ok=$ok && preg_match('/[0-9a-f]+/', $hash);
		
		//validate hash
		$ok=$ok && ($hash==substr(md5($change_id.$CONF['register_confirmation_secret']),0,16));
		if ($ok)
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			
			$user_emailchange_id=substr($change_id,1);
			
			$arr = $db->GetRow('select * from user_emailchange where user_emailchange_id='.$db->Quote($user_emailchange_id));	
			if ($arr['status']=='completed')
			{
				$status="alreadycomplete";
			}
			elseif(isset($arr['user_emailchange_id']))
			{
			
				//change email address
				$sql="update user set email=".$db->Quote($arr['newemail'])." where user_id=".$db->Quote($arr['user_id']);
				$db->Execute($sql);

				$sql="update user_emailchange set completed=now(), status='completed' where user_emailchange_id=$user_emailchange_id";
				$db->Execute($sql);


				$this->user_id=$arr['user_id'];
				$this->registered=true;

				$arr = $db->GetRow('select * from user where user_id='.$db->Quote($this->user_id).' limit 1');	
				foreach($arr as $name=>$value)
				{
					if (!is_numeric($name))
						$this->$name=$value;

				}

				//temporary nickname fix for beta accounts
				if (strlen($this->nickname)==0)
					$this->nickname=str_replace(" ", "", $this->realname);


				//setup forum user
				$this->_forumUpdateProfile();

				//log into forum too
				$this->_forumLogin();
				
				$status="ok";
			}
			else
			{
				//deleted change request?
				$status="fail";
			}
				
		}
		else
		{
			//hash mismatch or param problem
			$status="fail";
		}
		
		return $status;
	}
	
	/**
	* update user profile
	* profile array should contain website, nickname, realname flag. A
	* public_email entry, if present, will cause the public_email flag
	* to be set. The idea is to simply pass the $_POST array - all values
	* are checked for validity
	*/
	function updateProfile(&$profile, &$errors)
	{
		global $CONF;
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');   
		
		$ok=true;
		
		$profile['realname']=stripslashes($profile['realname']);
		$profile['nickname']=stripslashes($profile['nickname']);
		$profile['website']=stripslashes($profile['website']);

		// valid homesquare?
		$profile['grid_reference']=stripslashes($profile['grid_reference']);
		$gridreference='';
		$gs=new GridSquare();
		if (strlen($profile['grid_reference']))
		{
			$gsok=$gs->setByFullGridRef($profile['grid_reference']);
			if (!$gsok)
			{
				$ok=false;
				$errors['grid_reference']=$gs->errormsg;
			}
		}

			
		if (strlen($profile['realname']))
		{
			if (!isValidRealName($profile['realname']))
			{
				$ok=false;
				$errors['realname']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
			}
		}
		else
		{
			$ok=false;
			$errors['realname']='Please enter your real name, we use it to credit your photographs';
		}
		
		
		if (strlen($profile['website']) && !isValidURL($profile['website']))
		{
			//can we fix it?
			if (isValidURL("http://".$profile['website']))
			{
				$profile['website']="http://".$profile['website'];
			}
			else
			{
				$ok=false;
				$errors['website']='This doesn\'t appear to be a valid URL';
			}
		}
		
		
		//unique nickname, since you can log in with it
		if (isValidRealName($profile['nickname']))
		{
			//lets be sure it's unique
			$sql='select * from user where nickname='.$db->Quote(stripslashes($profile['nickname']))." and user_id<>{$this->user_id} limit 1";
			$r=$db->GetRow($sql);
			if (count($r))
			{
				$ok=false;
				$errors['nickname']='Sorry, this nickname is already taken by another user';
			}
			//todo check seperate table
		}
		else
		{
			$ok=false;
			if (strlen($errors['nickname']))
				$errors['nickname']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
			else
				$errors['nickname']='Please enter a nickname for use on the forums';
		}

		//attempting to change email address?
		if ($profile['email']!=$this->email)
		{
			if (isValidEmailAddress($profile['email']))
			{
				$errors['general']='To change your email address, '.
				'we\'ve sent an email to '.$profile['email'].' which contains '.
				'instructions on how to confirm the change.';
				$ok=false;
				
				
				//we need to send the user an email with a confirmation link
				//so we put the information into a table
				
				$db->Execute("insert into user_emailchange ".
					"(user_id, oldemail,newemail,requested,status)".
					"values(?,?,?,now(), 'pending')",
					array($this->user_id, $this->email, $profile['email']));
					
				$id=$db->Insert_ID();
				
				$url="http://".
					$_SERVER['HTTP_HOST'].'/reg/m'.$id.
					'/'.substr(md5('m'.$id.$CONF['register_confirmation_secret']),0,16);
						
				$msg="You recently requested the email address ".
				"for your account at ".$_SERVER['HTTP_HOST']." be changed to {$profile['email']}.\n\n".
				
				"To confirm, please click this link:\n\n".
				
				"$url\n\n".
				
				"If you do not wish to change your address, simply disregard this message";
				
				@mail($profile['email'], 'Please confirm your email address change', $msg,
				"From: Geograph Website <noreply@geograph.org.uk>");
				
				
			}
			else
			{
				$errors['email']='Invalid email address';
				$ok=false;
			}
			
		}

		
		if ($ok)
		{
			//about box is always public - col to be removed
			$profile['public_about']=1;
			$profile['use_age_group']=0;
			
			//age info is useless to others, nice for us, no need
			//to give use a public option
			
			//todo if nickname changed, add old one to seperate table
			
			$sql = sprintf("update user set 
				realname=%s,
				nickname=%s,
				website=%s,
				public_email=%d,
				search_results=%d,
				slideshow_delay=%d,
				about_yourself=%s,
				public_about=%d,
				age_group=%d,
				use_age_group=%d,
				home_gridsquare=%s,
				ticket_option=%s
				where user_id=%d",
				$db->Quote($profile['realname']),
				$db->Quote($profile['nickname']),
				$db->Quote($profile['website']),
				empty($profile['public_email'])?0:1,
				$profile['search_results'],
				$profile['slideshow_delay'],
				$db->Quote(stripslashes($profile['about_yourself'])),
				$profile['public_about']?1:0,
				$profile['age_group'],
				$profile['use_age_group']?1:0,
				$gs->gridsquare_id,
				$db->Quote($profile['ticket_option']),
				$this->user_id
				);

			if ($db->Execute($sql) === false) 
			{
				$errors['general']='error updating: '.$db->ErrorMsg();
				$ok=false;
			}
			else
			{
				//hurrah - it's all good - lets update ourself..
				
				//update gridimage_search too
				if ($this->realname != stripslashes($profile['realname'])) {
					$sql="update gridimage_search set realname=".$db->Quote(stripslashes($profile['realname'])).
						" where user_id = {$this->user_id}";
					$db->Execute($sql);
				}
				
				
				$this->realname=$profile['realname'];
				$this->nickname=$profile['nickname'];
				$this->website=$profile['website'];
				$this->public_email=isset($profile['public_email'])?1:0;
				if (isset($profile['sortBy'])) 
					$this->sortBy=stripslashes($profile['sortBy']);
				$this->search_results=stripslashes($profile['search_results']);
				$this->slideshow_delay=stripslashes($profile['slideshow_delay']);
				$this->about_yourself=stripslashes($profile['about_yourself']);
				$this->public_about=stripslashes($profile['public_about']);
				$this->age_group=stripslashes($profile['age_group']);
				$this->use_age_group=stripslashes($profile['use_age_group']);
				$this->grid_reference=$gs->grid_reference;	
				$this->ticket_option=stripslashes($profile['ticket_option']);
				$this->_forumUpdateProfile();
				
			}
		
		}
		
		return $ok;
	}
	
	/**
	* log the user out
	*/
	function logout()
	{
		//clear member vars
		$vars=get_object_vars($this);
		foreach($vars as $name=>$val)
		{
			unset($this->$name);
		}
		
		$this->_forumLogout();
		
		//initialise a few essentials
		$this->registered=false;
		$this->user_id=0;
		$this->realname="";
		
		//we've changed state, won't hurt to use a new
		//session id...
		session_regenerate_id(); 
		
		//also clear the autologin cookie as doesnt make sence to keep
		setcookie('autologin', '', time()-3600*24*365,'/');  
		
	}

	
	/**
	* force inline login if user isn't authenticated
	*/
	function mustHavePerm($perm)
	{
		//not logged in? do that first
		if (!$this->registered)
		{
			//do an inline login
			$this->login();
		}
		
		//to reach here, user is logged in, lets check the perms
		if (strpos($this->rights, $perm)===false)
		{
			//user is logged in, but hasn't got sufficient rights
			$smarty = new GeoGraphPage;
			$smarty->assign('required', $perm);
			$smarty->display('no_permission.tpl');
			exit;
		}
		else
		{
			//user has the correct rights.
		}
		
	}
	
	/**
	* got perm?
	*/
	function hasPerm($perm)
	{
		return $this->registered && (strpos($this->rights, $perm)!==false);
	}
	
	function basicAuthLogin() {
		if (isset($_SERVER['PHP_AUTH_USER']))
		{
			$email=stripslashes(trim($_SERVER['PHP_AUTH_USER']));
			$password=stripslashes(trim($_SERVER['PHP_AUTH_PW']));
			
			$db = NewADOConnection($GLOBALS['DSN']);

			$sql="";
			if (isValidEmailAddress($email))
				$sql='select * from user where email='.$db->Quote($email).' limit 1';
			elseif (isValidRealName($email))
				$sql='select * from user where nickname='.$db->Quote($email).' limit 1';


			if (strlen($sql))
			{
				//user registered?
				$arr = $db->GetRow($sql);	
				if (count($arr))
				{
					//passwords match?
					if ($arr['password']==$password)
					{
						//final test = if they have no rights, they haven't confirmed
						//their registration
						if (strlen($arr['rights']))
						{
							//copy user fields into this object
							foreach($arr as $name=>$value)
							{
								if (!is_numeric($name))
									$this->$name=$value;
							}

							$this->registered=true;
							$logged_in=true;
						}
						else
						{
							$error ='You must confirm your registration by following the link in the email sent to '.$email;
						}
					}
					else
					{
						//speak friend and enter					
						$error ='Wrong password - don\'t forget passwords are case-sensitive';
					}

				}
				else
				{
					//sorry son, your name's not on the list
					$error ='This email address or nickname is not registered';
				}
			}
			else
			{
				$error ='This is not a valid email address or nickname';

			}
		} 
		else 
		{
			$error ='No Credentials Supplied';
		}
		
		
		//failure to login means we never return - we show a login page
		//instead...
		if (!$logged_in)
		{
			header('WWW-Authenticate: Basic realm="Geograph"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'Error: Unable to Authenticate - '.$error;
			exit;
		}
	}
	
	/**
	* force inline login if user isn't authenticated
	* only return after successful login
	*/
	function login($inline=true)
	{
		$logged_in=false;
		
		if (!$this->registered)
		{
			$errors=array();
				
			//lets see if we are processing a login?
			if (isset($_POST['email']))
			{
				$email=stripslashes(trim($_POST['email']));
				$password=stripslashes(trim($_POST['password']));
				$remember_me=isset($_POST['remember_me'])?1:0;
				
				
				$db = NewADOConnection($GLOBALS['DSN']);

				$sql="";
				if (isValidEmailAddress($email))
					$sql='select * from user where email='.$db->Quote($email).' limit 1';
				elseif (isValidRealName($email))
					$sql='select * from user where nickname='.$db->Quote($email).' limit 1';
				
				
				if (strlen($sql))
				{
					//user registered?
					$arr = $db->GetRow($sql);	
					if (count($arr))
					{
						//passwords match?
						if ($arr['password']==$password)
						{
							//final test = if they have no rights, they haven't confirmed
							//their registration
							if (strlen($arr['rights']))
							{
								//copy user fields into this object
								foreach($arr as $name=>$value)
								{
									if (!is_numeric($name))
										$this->$name=$value;
								}
								
								//temporary nickname fix for beta accounts
								if (strlen($this->nickname)==0)
									$this->nickname=str_replace(" ", "", $this->realname);

								//give user a remember me cookie?
								if ($remember_me)
								{
									$token = md5(uniqid(rand(),1)); 
									$db->query("insert into autologin(user_id,token) values ('{$this->user_id}', '$token')");
									setcookie('autologin', $this->user_id.'_'.$token, time()+3600*24*365,'/');  
								}
								
								//we're changing privilege state, so we should
								//generate a new session id to avoid fixation attacks
								session_regenerate_id(); 
								
								$this->registered=true;
								$logged_in=true;
								
								//log into forum too
								$this->_forumLogin();
								
							}
							else
							{
								$errors['general']='You must confirm your registration by following the link in the email sent to '.$email;
							}
						}
						else
						{
							//speak friend and enter					
							$errors['password']='Wrong password - don\'t forget passwords are case-sensitive';
						}

					}
					else
					{
						//sorry son, your name's not on the list
						$errors['email']='This email address or nickname is not registered';
					}
				}
				else
				{
					$errors['email']='This is not a valid email address or nickname';
					
				}
				
			}
			
			//failure to login means we never return - we show a login page
			//instead...
			if (!$logged_in)
			{
				$smarty = new GeoGraphPage;
				
				$smarty->assign('remember_me', isset($_COOKIE['autologin'])?1:0);
				$smarty->assign('inline', $inline);
				$smarty->assign('email', $email);
				$smarty->assign('password', $password);
				$smarty->assign('errors', $errors);
				$smarty->display('login.tpl');
				exit;
			}
			
		
		}
		else
		{
			$logged_in=true;
		}
		
		//we're logged in
		return $logged_in;
	}
	
	/**
	* attempt to authenticate user from persistent cookie
	*/
	function autoLogin()
	{
		if(isset($_COOKIE['autologin']))
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			
			$errorNumber = -1;
			$valid=false;
			$bits=explode('_', $_COOKIE['autologin']);
			if ((count($bits)==2) &&
			    is_numeric($bits[0]) &&
			    preg_match('/^[a-f0-9]{32}$/' , $bits[1]))
			{
				$clause="user_id='{$bits[0]}' and token='{$bits[1]}'";
				$row=$db->GetRow("select * from autologin where $clause limit 1");
				
				//log the errornumber (we use in case the db lookup failed)
				$errorNumber = $db->ErrorNo();
					
				if (count($row))
				{
					//log the user in
					$sql='select * from user where user_id='.$db->Quote($bits[0]).' limit 1';
					$user = $db->GetRow($sql);
					
					//log the errornumber (we use in case the db lookup failed) 
					$errorNumber = $db->ErrorNo();
					
					if (count($user))
					{
						$valid=true;
						
						foreach($user as $name=>$value)
						{
							if (!is_numeric($name))
								$this->$name=$value;
						}

						//temporary nickname fix for beta accounts
						if (strlen($this->nickname)==0)
							$this->nickname=str_replace(" ", "", $this->realname);

						//we're changing privilege state, so we should
						//generate a new session id to avoid fixation attacks
						session_regenerate_id(); 

						$this->registered=true;
						$this->autologin=true;

						//log into forum
						$this->_forumLogin();

						//delete the autologin, we've used it
						$db->query("delete from autologin where $clause");

						//given the user a new one
						$token = md5(uniqid(rand(),1)); 
						$db->query("insert into autologin(user_id,token) values ('{$this->user_id}', '$token')");
						setcookie('autologin', $this->user_id.'_'.$token, time()+3600*24*365,'/');
					}
				}
			}
			if ($errorNumber != 0) {
				die("Server Error, please wait 10 seconds then press F5, and click Yes if asked to confirm. This measure is to hopefully perserve what you are working on. If you still get this message after repeated tries then there is nothing for it but to click back and try again.");
				exit;
			}

			//clear the cookie?
			if (!$valid)
			{
				setcookie('autologin', '', time()-3600*24*365,'/');
			}
		}
	}
	
	/**
	* Updates forum profile to keep the forum software in sync with us
	*/
	function _forumUpdateProfile()
	{
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');   
	
		//we maintain a direct user_id to user_id mapping with the minibb 
		//forum software....
	
		
		//do we have a forum user?
		$existing=$db->GetRow("select * from geobb_users where user_id='{$this->user_id}' limit 1");
		if (count($existing))
		{
			//update profile
			$sql="update geobb_users set username=".$db->Quote($this->nickname).
				", user_email=".$db->Quote($this->email).
				", user_password=md5(".$db->Quote($this->password).")".
				", user_website=".$db->Quote($this->website).
				", user_viewemail=".$this->public_email.
				(isset($this->sortBy)?', user_sorttopics = '.$this->sortBy:'').
				" where user_id={$this->user_id}";
				
			$db->Execute($sql);	
		}
		else
		{
			//create new profile
			$sql="insert into geobb_users(user_id,username, user_regdate,user_password,user_email,user_website,user_viewemail) values (".
				$this->user_id.",".
				$db->Quote($this->nickname).",".
				"now(),".
				"md5(".$db->Quote($this->password)."),".
				$db->Quote($this->email).",".
				$db->Quote($this->website).",".
				$this->public_email.")";

			
			
			$db->Execute($sql);		
				
		}
		
		
	}

	/**
	* Setup a forum session so use is automatically logged in
	*/
	function _forumLogin()
	{
		$this->_forumUpdateProfile();
		
		$passmd5=md5($this->password);
		$expiry=time()+108000;
		
		//we don't need a permanent cookie
		//setcookie('geographbb', 
		//	$this->nickname.'|'.$passmd5.'|'.$expiry, 
		//	$expiry);
			
		$_SESSION['minimalistBBSession']=$this->nickname.'|'.$passmd5.'|'.$expiry;
	}

	/**
	* Log out of forum
	*/
	function _forumLogout()
	{
		//we clear the miniBB cookie here as early betas
		//did set it
		setcookie('geographbb', '', time()-108000);
		unset($_SESSION['minimalistBBSession']);
	}
	
	
}
?>

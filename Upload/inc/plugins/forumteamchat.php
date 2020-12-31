<?php
//disallow unauthorize access
if(!defined("IN_MYBB")) {
	die("You are not authorize to view this");
}

$plugins->add_hook('global_start', 'forumteamchat_start');

//Plugin Information
function forumteamchat_info()
{
	return array(
		'name' => 'MyBB Forum Team Live Chat Plugin',
		'author' => 'Sunil Baral',
		'website' => 'https://github.com/snlbaral',
		'description' => 'This plugins allows mybb forum team to live chat',
		'version' => '1.1.1',
		'compatibility' => '18*',
		'guid' => '',
	);
}

//Plugin Installation
function forumteamchat_install()
{
	global $db;
	$collation = $db->build_create_table_collation();
	if (!$db->table_exists('forumteamchats')) {
        switch ($db->type) {
            case 'pgsql':
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "forumteamchats(
                        id serial,
                        uid int NOT NULL,
                        username varchar(100) NOT NULL,
                        msg varchar(255) NOT NULL DEFAULT '',
                        dateline timestamp NOT NULL,
                        endtime timestamp NOT NULL,
                        PRIMARY KEY (id)
                    );"
                );
                break;
            default:
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "forumteamchats(
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `uid` int(10) unsigned NOT NULL,
                        `username` varchar(100) NOT NULL,
                        `msg` varchar(255) NOT NULL DEFAULT '',                        
                        `dateline` datetime NOT NULL,
                        `endtime` datetime NOT NULL,                        
                        PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM{$collation};"
                );
                break;
        }
	}
}


function forumteamchat_is_installed()
{
	global $db;
	return $db->table_exists('forumteamchats');
}

//Plugin Uninstall
function forumteamchat_uninstall()
{
	global $db;
	if ($db->table_exists('forumteamchats')) {
		$db->drop_table('forumteamchats');
	}
}

//Activate Plugin
function forumteamchat_activate()
{
	global $db, $mybb, $settings, $PL;

	//Admin CP Settings
	$forumteamchat_group = array(
		'gid' => '',
		'name' => 'forumteamchat',
		'title' => 'MyBB Forum Team Live Chat Plugin',
		'description' => 'Settings for MyBB Forum Team Live Chat Plugin',
		'disporder' => '1',
		'isdefault' =>  '0',
	);
	$db->insert_query('settinggroups',$forumteamchat_group);
	$gid = $db->insert_id();
	//Enable or Disable
	$forumteamchat_enable = array(
		'sid' => 'NULL',
		'name' => 'forumteamchat_enable',
		'title' => 'Do you want to enable this plugin?',
		'description' => 'If you set this option to yes, this plugin will start working.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => intval($gid),
	);
	//Allowed User Group
	$forumteamchat_allowed_group = array(
		'sid' => 'NULL',
		'name' => 'forumteamchat_allowed_group',
		'title' => 'Which groups this plugin is enable for?',
		'description' => 'Add gid of group that will be able to use this plugin.',
		'optionscode' => 'groupselect',
		'value' => '3,4,6',
		'disporder' => 1,
		'gid' => intval($gid),
	);
	$db->insert_query('settings',$forumteamchat_enable);
	$db->insert_query('settings',$forumteamchat_allowed_group);
	rebuild_settings();

	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_header',
		'template' => $db->escape_string('
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="inc/plugins/MybbStuff/forumteamchat/forumteamchat.css">
<div id="forClose">
<i class="fa fa-times" aria-hidden="true"></i>
</div>
<div id="forOpen">
<i class="fa fa-comments" aria-hidden="true"></i>
</div>
<div class="bdy">
<div id="chathead">
<div class="alphabet">
<i class="fa fa-users" aria-hidden="true"></i> '.$mybb->settings["bbname"].' Team
</div>
<div id="chatbody">
<div id="chatbodymsg">
			'),
		'sid' => '-1',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_row_self',
		'template' => $db->escape_string('
<div class="wholemsg" style="float:right;margin-top:6px"><div class="username" title="{$chat_username}"></div><div class="msgbx" onclick="return msgClick(this);" onmouseout="return msgOff(this);" id="msgDivA" dummy="{$unq}" style="background: rgb(0,132,255);color: #fff;float:right" title="{$sentdate}">{$chat_message}</div><div id="{$unq}" style="color: #666;font-size: 6px;visibility: hidden;clear:both;float:right;margin-top:2px">{$sentdate}</div></div><br>
			'),
		'sid' => '-1',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_row_others',
		'template' => $db->escape_string('
<div class="wholemsg"><div class="username" title="{$chat_username}"><img src="{$chat_avatar}" class="pphead" style="border: 2px solid {$color}"></div><div class="msgbx" onclick="return msgClick(this);" onmouseout="return msgOff(this);" id="msgDivD" dummy="{$unq}" title="{$sentdate}">{$chat_message}</div><div id="{$unq}" style="color: #666;margin-left: 54px;font-size: 6px;visibility: hidden;margin-top:-2px">{$sentdate}</div></div><br>
			'),
		'sid' => '-1',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_index',
		'template' => $db->escape_string('
{$items}
			'),
		'sid' => '-1',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_footer',
		'template' => $db->escape_string('
</div>
</div>
<form action="" data-route="{$sendurl}" method="post" id="chatform">
	<input type="text" name="msg" autocomplete="off" required>
	<input type="submit" value="Send" id="send">
</form>
</div>
</div>
<script src="jscripts/forumteamchat.js"></script>
			'),
		'sid' => '-1',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);


	//Activate in header template
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote("{\$awaitingusers}") . "#i", "{\$awaitingusers}\r\n
        {\$forumteamchat}");
}


//Deactivate Plugin
function forumteamchat_deactivate()
{
	global $db, $mybb, $settings, $PL;
	//Templates Delete
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('forumteamchat_enable')");
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('forumteamchat_allowed_group')");
	$db->query("DELETE from ".TABLE_PREFIX."settinggroups WHERE name IN ('forumteamchat')");
	$db->query("DELETE from ".TABLE_PREFIX."templates WHERE title LIKE 'forumteamchat%' AND sid='-1'");
	rebuild_settings();

	//Deactive from header template
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote("\r\n
        {\$forumteamchat}") . "#i", "", 0);
}

//Display Function
function forumteamchat_start()
{
	global $db, $mybb, $templates, $forumteamchat;

	$allowed_group = explode(',', $mybb->settings['forumteamchat_allowed_group']);
	$usergroup = $mybb->user['usergroup'];
	
	//If usergroup is in allowed user group for forum chat
	if($usergroup!=1 && (in_array($usergroup, $allowed_group) || in_array('-1', $allowed_group))) {

		//Header Part
		$stuff .= $templates->get('forumteamchat_header');

		//Message Part
		$default_username = $mybb->user['username'];

		//Check if previous chats exist
		$query = $db->query("SELECT * from mybb_forumteamchats ORDER By id");
		$rows = $db->num_rows($query);
		$count = 0;
		if($rows>0) {
			while($row = $db->fetch_array($query)) {
				$chat_username = htmlspecialchars_uni($row['username']);
				$chat_message = htmlspecialchars_uni($row['msg']);

				//Get Avatar of User
				$tmpquery = $db->query("SELECT * from mybb_users WHERE username='$chat_username'");
				$result = $db->fetch_array($tmpquery);
				$chat_avatar = $result['avatar'];
				if($chat_avatar==NULL) {
					$chat_avatar = 'images/default_avatar.png';
				}

				//Avatar Border Color
				$gid = $row['gid'];
				if($gid==4) {
					$color = "green";
				} elseif ($gid==3) {
					$color = "#cc00cc";
				} else {
					$color = "#000066";
				}

				//Display Date on click
				$sentdate = $row['dateline'];
				$currentdate = date("Y-m-d H:i:s");
				$endtime = date('Y-m-d H:i:s', strtotime($sentdate. ' + 24 hours'));
				$unq = uniqid();
				if($endtime<$currentdate) {
					$sentdate = $sentdate;
				} else {
					$sub = strtotime($currentdate)-strtotime($sentdate);
					$copmhour = floor($sub / 3600);
					if($copmhour<1) {
						$sentdate = floor($sub/60).' Minutes Ago';
					} else {
						$sentdate = floor($sub / 3600).' Hours Ago';
					}
				}

				//Putting Messages in right place left/right
				if($default_username!=$chat_username) {
		        	eval("\$items .= \"".$templates->get("forumteamchat_row_others")."\";");
				} else {
		        	eval("\$items .= \"".$templates->get("forumteamchat_row_self")."\";");			
				}
			}
		}
		$stuff .= $templates->get('forumteamchat_index');

		$sendurl = $mybb->settings['bburl'].'/inc/plugins/MybbStuff/forumteamchat/forumteamchat_send.php';

		//Footer/Input Field Part
		$stuff .= $templates->get('forumteamchat_footer');

		eval("\$forumteamchat = \"".$stuff."\";");
	}
}
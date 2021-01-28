<?php
//disallow unauthorize access
if(!defined("IN_MYBB")) {
	die("You are not authorize to view this");
}

$plugins->add_hook('global_start', 'forumteamchat_start');

//Plugin Information
function forumteamchat_info()
{
	global $mybb;

	if($mybb->settings['forumteamchat_enable'] == 1){

		$config = '<div style="float: right;"><a href="index.php?module=config-settings&action=change&search=forumteamchat" style="color:#035488; padding: 21px; text-decoration: none;">Configurar</a></div>';

	}

	else if($mybb->settings['forumteamchat_enable'] == 0){

		$config = '<div style="float: right;"><span style="color:Red; padding: 21px; text-decoration: none;">Plugin disabled</span></div>';

	}
	return array(
		'name' => 'MyBB Forum Team Live Chat Plugin',
		'author' => 'Sunil Baral',
		'website' => 'https://github.com/snlbaral',
		'description' => 'This plugins allows mybb forum team to live chat'.$config,
		'version' => '2.0',
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
                        seenby text NOT NULL DEFAULT '',
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
                        `seenby` text NOT NULL DEFAULT '',                        
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
		'gid' => (int)'',
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
	$forumteamchat_deleted = array(
		'sid' => 'NULL',
		'name' => 'forumteamchat_deleted',
		'title' => 'Do you want to display deleted message?',
		'description' => 'If you set this option to yes, deleted messages will show a message "{username} unsent a message".',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 1,
		'gid' => intval($gid),
	);
	$db->insert_query('settings',$forumteamchat_enable);
	$db->insert_query('settings',$forumteamchat_allowed_group);
	$db->insert_query('settings', $forumteamchat_deleted);
	rebuild_settings();

	$q = $db->simple_select("templategroups", "COUNT(*) as count", "title = 'Forum Team Chat'");
	$c = $db->fetch_field($q, "count");
	$db->free_result($q);
	
	if($c < 1)
	{
		$ins = array(
			"prefix"		=> "forumteamchat",
			"title"			=> "Forum Team Chat",
		);
		$db->insert_query("templategroups", $ins);
	}

	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_main',
		'template' => $db->escape_string('
{$fheader}
{$findex}
{$ffooter}
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);



	//Templates
	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_header',
		'template' => $db->escape_string('
{$headerinclude}
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="inc/plugins/MybbStuff/forumteamchat/forumteamchat.css">
<div class="forumteamchat-unreadmsg" style="display: none">{$forum_uncountmsg}</div>
<div class="bdy">
<div id="chathead">
<div class="alphabet">
<i class="fa fa-users" aria-hidden="true"></i> '.$mybb->settings["bbname"].' Team {$clearchat}<span class="scrolltobottom" title="Scroll to bottom"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
</div>
<div id="chatbody">
<div id="chatbodymsg">
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_row_self',
		'template' => $db->escape_string('
<div class="wholemsg self-wholemsgdiv" onmouseover="return dotsVis(this);"><div class="username" title="{$chat_username}"></div><div class="remove-message remove-{$messageid}">Remove</div><i class="fa fa-ellipsis-h dots" aria-hidden="true" onclick="return rmvIt(this);" msgid="{$messageid}"></i><div class="msgbx self-message" onclick="return msgClick(this);" onmouseout="return msgOff(this);" id="msgDivA" dummy="{$unq}" title="{$sentdate}">{$chat_message}</div><div id="{$unq}" class="self-sentdate">{$sentdate}</div></div><br>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);

	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_row_others',
		'template' => $db->escape_string('
<div class="wholemsg others-wholemsgdiv"><div class="username" title="{$chat_username}"><img src="{$chat_avatar}" class="pphead" style="border: 2px solid {$color}"></div><div class="msgbx others-message" onclick="return msgClick(this);" onmouseout="return msgOff(this);" id="msgDivD" dummy="{$unq}" title="{$sentdate}">{$chat_message}</div><div id="{$unq}" class="others-sentdate">{$sentdate}</div></div><br>
			'),
		'sid' => '-2',
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
		'sid' => '-2',
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
	<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
	<input type="submit" value="Send" id="send">
</form>
</div>
</div>
<script src="jscripts/forumteamchat.js"></script>
			'),
		'sid' => '-2',
		'version' => $mybb->version_code,
		'dateline' => time(),
	);
	$db->insert_query('templates',$insert_temp);



	$insert_temp = array(
		'tid' => NULL,
		'title' => 'forumteamchat_iframe',
		'template' => $db->escape_string('
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
<style>
#forumteamchatFrame {
	height: 450px;
	position: fixed;
	right: 2%;
	border: 0;
	z-index: 99999;
	border-radius: 6px;
	border: 1px solid #999;
	bottom: 5px;
	width: 330px;
	visibility: hidden;
}
#forumteamchatClose, #forumteamchatOpen {
	position: fixed;
	right: 2%;
	z-index: 999;
	background: #5876ab;
	padding: 6px;
	text-align: center;
	color: #fff;
	cursor: pointer;
	border-radius: 50%;
	border: 2px solid #fff;
	bottom: 456px;
}

#forumteamchatClose {
	display: none;
}

#forumteamchat-unreadmsg {
    color: #fff !important;
    position: absolute;
    z-index: 999;
    top: -13px;
    background: green;
    width: 19px;
    border-radius: 50%;
    height: 19px;
    right: -13px;
    border: 2px solid #fff;
    line-height: 19px;
    font-size: 12px;
    display: none;
}
</style>
<div id="forumteamchatClose">
<i class="fa fa-times" aria-hidden="true"></i>
</div>
<div id="forumteamchatOpen">
<span id="forumteamchat-unreadmsg"></span><i class="fa fa-comments" aria-hidden="true"></i>
</div>
<iframe src="'.$mybb->settings['bburl'].'/forum-teamchat.php" id="forumteamchatFrame"></iframe>
<script>
document.getElementById("forumteamchatFrame").onload = function() {
	var unframe = document.getElementById("forumteamchatFrame");
	var undoc = unframe.contentDocument;
	var unbody = undoc.body;
	var unelm = unbody.getElementsByClassName("forumteamchat-unreadmsg")[0];
	document.getElementById("forumteamchat-unreadmsg").innerHTML = unelm.innerHTML;
	var uncom = unelm.innerHTML;
	if(uncom>0) {
		document.getElementById("forumteamchat-unreadmsg").style.display = "block";
	} else {
		document.getElementById("forumteamchat-unreadmsg").style.display = "none";
	}
}

document.getElementById("forumteamchatOpen").onclick = function() {
	document.getElementById("forumteamchatOpen").style.display = "none";
	document.getElementById("forumteamchatClose").style.display = "initial";
	document.getElementById("forumteamchatFrame").style.visibility = "visible";
	var myframe = document.getElementById("forumteamchatFrame");
	var mydoc = myframe.contentDocument;
	var mybody = mydoc.body;
	var url = "forum-teamchat.php";
	var fde = new FormData();
	fde.append("action","markasread");
	$.ajax({
		url: url,
		type: "POST",
		data: fde,
		dataType: "json",
		success: function(data) {
			//console.log(data.res);
		},
		error: function(e) {
		},
		cache: false,
		contentType: false,
		processData: false,
	});

}

document.getElementById("forumteamchatClose").onclick = function() {
	document.getElementById("forumteamchatOpen").style.display = "initial";
	document.getElementById("forumteamchatClose").style.display = "none";
	document.getElementById("forumteamchatFrame").style.visibility = "hidden";
}
</script>
			'),
		'sid' => '-2',
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
	$db->query("DELETE from ".TABLE_PREFIX."settings WHERE name IN ('forumteamchat_deleted')");
	$db->query("DELETE from ".TABLE_PREFIX."settinggroups WHERE name IN ('forumteamchat')");
	$db->query("DELETE from ".TABLE_PREFIX."templategroups WHERE prefix IN ('forumteamchat')");
	$db->query("DELETE from ".TABLE_PREFIX."templates WHERE title LIKE 'forumteamchat%'");
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

	if($mybb->settings['forumteamchat_enable']==1) {
		$allowed_group = explode(',', $mybb->settings['forumteamchat_allowed_group']);
		$usergroup = $mybb->user['usergroup'];
		if($usergroup!=1 && (in_array($usergroup, $allowed_group) || in_array('-1', $allowed_group))) {
			eval("\$forumteamchat = \"".$templates->get("forumteamchat_iframe")."\";");
		}
	}
}
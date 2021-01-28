<?php
// Boring stuff..
define('IN_MYBB', 1);

$templatelist = 'forumteamchat_footer, forumteamchat_header, forumteamchat_row_others, forumteamchat_row_self, forumteamchat_index';
require_once './global.php';

//Errors
if ((int) $mybb->user['uid'] < 1) {
	error_no_permission();
}

//Information
$user = $mybb->user;
$username = $user['username'];
$gid = $user['usergroup'];
$uid = (int)$user['uid'];
$curr = date("Y-m-d H:i:s");

//We'll use this to delete chat history later
//Replace 5 days with your needs. i.e. 10 days, 30 days
$endtime = date('Y-m-d H:i:s', strtotime($curr. ' + 5 days'));

//No more execution for guests
if($gid=="1") {
exit;
}

//Remove Message
if(isset($mybb->input['action']) && $mybb->input['action']=="remove" && $mybb->input['msgid']!=="") {
	$msgid = (int)$mybb->input['msgid'];
	//Let's check msg uid
	$query = $db->simple_select("forumteamchats","*","id='$msgid'");
	$row = $db->fetch_array($query);
	$msg_uid = $row['uid'];
	//If user and removing message is from same uid
	if($msg_uid==$uid) {
		$update_array = array(
			'msg' => $db->escape_string('NULL&UNSENT'),
		);
		$db->update_query("forumteamchats",$update_array,"id='$msgid'");
		echo json_encode(array('response'=>'removed'));
		exit;
	}

//Clear Chat History
} elseif(isset($mybb->input['action']) && $mybb->input['action']=="clearchat") {
	//Only Admin is able to clear chat history	
	if($uid=="1") {
		$db->delete_query("forumteamchats","endtime<'$curr'");
		$url = $_SERVER['HTTP_REFERER'];
		echo json_encode(array("response"=>"cleared","url"=>$url));
		exit;		
	} else {
		echo json_encode(array("response"=>"Permission Denied."));
		exit;
	}

} else {
//Send Message
	if(isset($mybb->input['msg']) && $mybb->input['msg']!=NULL && $mybb->input['msg']!=="") {
		$postkey = $mybb->input['my_post_key'];	
		verify_post_check($postkey);
		$allowed_group = explode(',', $mybb->settings['forumteamchat_allowed_group']);
		$usergroup = $mybb->user['usergroup'];
		//If usergroup is in allowed user group for forum chat
		if($usergroup!=1 && (in_array($usergroup, $allowed_group) || in_array('-1', $allowed_group))) {
			$msg = $mybb->input['msg'];
			$msg = trim(htmlentities(strip_tags($msg)));
			$seenby = array();
			$seenby[] = " ".(int)$uid." ";
			$seenby = implode(",", $seenby);
			$sql = array(
				'uid' => (int)$uid,
				'username' => $db->escape_string($username),
				'msg' => $db->escape_string($msg),
				'dateline' => $curr,
				'endtime' => $endtime,
				'seenby' => $seenby,
			);
			$stm = $db->insert_query("forumteamchats",$sql);
			
			if($stm) {
				echo json_encode(array("response" => "sent"));
				exit;
			} else {
				echo json_encode(array("response" => "failed"));
				exit;
			}
		}
	}

}

//Mark Messages Read
if(isset($mybb->input['action']) && $mybb->input['action']=="markasread") {
	$uid = (int)$uid;
	$marksql = $db->simple_select("forumteamchats","*","seenby NOT LIKE '% $uid %'");
	$markrows = $db->num_rows($marksql);
	if($markrows>0) {
		while($markrow = $db->fetch_array($marksql)) {
			$msgid = (int)$markrow['id'];
			$markarr = $markrow['seenby'];
			if($markarr != '') {
				$markarr = explode(",", $markarr);
			} else {
				$markarr = array();
			}
			$markarr[] = " ".(int)$uid." ";
			$readarr = implode(",", $markarr);
			$update_array = array(
				"seenby" => $readarr,
			);
			$db->update_query("forumteamchats",$update_array,"id='$msgid'");
		}
	}
	echo json_encode(array("res"=>"success"));
	exit;
}


if($mybb->settings['forumteamchat_enable']==1) {
	$allowed_group = explode(',', $mybb->settings['forumteamchat_allowed_group']);
	$usergroup = $mybb->user['usergroup'];

	//If usergroup is in allowed user group for forum chat
	if($usergroup!=1 && (in_array($usergroup, $allowed_group) || in_array('-1', $allowed_group))) {


		//Message Part
		$default_username = $mybb->user['username'];

		//Check if previous chats exist
		$query = $db->simple_select('forumteamchats', '*', '', ['order_by' => 'id']);
		$rows = $db->num_rows($query);
		$count = 0;
		if($rows>0) {
			while($row = $db->fetch_array($query)) {
				$chat_username = $db->escape_string($row['username']);
				$chat_message = $db->escape_string($row['msg']);

				//Get Avatar of User
				$tmpquery  = $db->simple_select('users', '*', "username='{$chat_username}'");
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
					//If message is unsent
					if($chat_message=="NULL&UNSENT") {
						$chat_message = $chat_username." unsent a message";
						$delete_msg = '<div class="wholemsg others-wholemsgdiv"><div class="username" title="'.$chat_username.'"><img src="'.$chat_avatar.'" class="pphead" style="border: 2px solid '.$color.'"></div><div class="msgbx others-message-removed" id="msgDivD" title="'.$sentdate.'">'.$chat_message.'</div></div><br>';
						eval("\$items .= \"\$delete_msg\";");
					//Else
					} else {
		        		eval("\$items .= \"".$templates->get("forumteamchat_row_others")."\";");
					}

				} else {
					//If message is unsent
					if($chat_message=="NULL&UNSENT") {
						$chat_message = "You unsent a message";
						if($settings['forumteamchat_deleted']==1) {
							$delete_msg = '<div class="wholemsg self-wholemsgdiv"><div class="username" title="'.$chat_username.'"></div><div class="msgbx self-message-removed" id="msgDivA" title="'.$sentdate.'">'.$chat_message.'</div></div><br>';
						}
						eval("\$items .= \"\$delete_msg\";");

					} else {
						$messageid = $row['id'];
		        		eval("\$items .= \"".$templates->get("forumteamchat_row_self")."\";");
					}

				}
			}
		}

		$sendurl = $mybb->settings['bburl'].'/forum-teamchat.php';

		$unreadsql = $db->simple_select("forumteamchats","*","seenby NOT LIKE '% $uid %'");
		$forum_uncountmsg = $db->num_rows($unreadsql);

		//Enable clear chat option for admin
		if($mybb->user['uid']=="1") {
			$clearchat = '&nbsp(<span class="clear-chathistory" title="Clear Chat History">Clear <i class="fa fa-times"></i></span>)';
		}


		//Display
		$content = '';
		eval("\$fheader = \"".$templates->get("forumteamchat_header")."\";");
		eval("\$findex = \"".$templates->get("forumteamchat_index")."\";");
		eval("\$ffooter = \"".$templates->get("forumteamchat_footer")."\";");
		eval("\$content = \"" . $templates->get('forumteamchat_main') . "\";");
		output_page($content);
	} else {
		error_no_permission();
	}
} else {
	error_no_permission();
}
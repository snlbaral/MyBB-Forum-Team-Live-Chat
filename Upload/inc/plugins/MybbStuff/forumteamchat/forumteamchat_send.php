<?php
define("IN_MYBB", 1);
require("../../../../global.php");

//Information
$user = $mybb->user;
$username = $user['username'];
$gid = $user['usergroup'];
$uid = $user['uid'];
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
	}

//Clear Chat History
} elseif(isset($mybb->input['action']) && $mybb->input['action']=="clearchat") {
	//Only Admin is able to clear chat history	
	if($uid=="1") {
		$db->delete_query("forumteamchats","endtime<'$curr'");
		$url = $_SERVER['HTTP_REFERER'];
		echo json_encode(array("response"=>"cleared","url"=>$url));		
	} else {
		echo json_encode(array("response"=>"Permission Denied."));
	}

} else {
//Send Message
	if(isset($mybb->input['msg']) && $mybb->input['msg']!=NULL && $mybb->input['msg']!=="") {

		$allowed_group = explode(',', $mybb->settings['forumteamchat_allowed_group']);
		$usergroup = $mybb->user['usergroup'];
		//If usergroup is in allowed user group for forum chat
		if($usergroup!=1 && (in_array($usergroup, $allowed_group) || in_array('-1', $allowed_group))) {
			$msg = $mybb->input['msg'];
			$msg = trim(htmlentities(strip_tags($msg)));
			$sql = array(
				'uid' => (int)$uid,
				'username' => $db->escape_string($username),
				'msg' => $db->escape_string($msg),
				'dateline' => $curr,
				'endtime' => $endtime,
			);
			$stm = $db->insert_query("forumteamchats",$sql);
			
			if($stm) {
				echo json_encode(array("response" => "sent"));
			} else {
				echo json_encode(array("response" => "failed"));
			}
		}
	}

}
?>
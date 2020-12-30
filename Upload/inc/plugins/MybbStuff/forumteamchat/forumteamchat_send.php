<?php
define("IN_MYBB", 1);
require("../../../../global.php");
$user = $mybb->user;
$username = $user['username'];
//$avatar = $user['avatar'];
$gid = $user['usergroup'];
$uid = $user['uid'];
$curr = date("Y-m-d H:i:s");
$endtime = date('Y-m-d H:i:s', strtotime($curr. ' + 5 days'));
if(isset($mybb->input['msg']) && $mybb->input['msg']!=NULL && $mybb->input['msg']!=="") {
	$msg = $mybb->input['msg'];
	$msg = trim(htmlentities(strip_tags($msg)));
	$sql = array(
		'uid' => $uid,
		'username' => $username,
		'msg' => $msg,
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
?>
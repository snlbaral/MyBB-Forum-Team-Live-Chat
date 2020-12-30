<?php
define("IN_MYBB", 1);
require("../../../../global.php");
$user = $mybb->user;
$username = $user['username'];
$avatar = $user['avatar'];
$gid = $user['usergroup'];
$uid = $user['uid'];
$curr = date("Y-m-d H:i:s");
$endtime = date('Y-m-d H:i:s', strtotime($curr. ' + 5 days'));
if(isset($_POST) && $_POST['msg']!=NULL && $_POST['msg']!=="") {
	$msg = $_POST['msg'];
	$msg = trim(htmlentities(strip_tags($msg)));
	$unq = rand (00 , 99 );
	$username = $username;
	$username = $username;
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
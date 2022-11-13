<?php
//Requesting files
include "../../incl/lib/connection.php";
require_once "../../incl/lib/mainLib.php";
require_once "../incl/dashboardLib.php";
$dl = new dashboardLib();
$gs = new mainLib();
//redicret if not logined
$urlWas = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$urlWas = explode('?', $urlWas); $_SESSION["urlWas"] = $urlWas[0];
if(!isset($_SESSION["accountID"]) || !$_SESSION["accountID"]) exit(header("Location: ../login/login.php"));
//Checking permissions
$perms = $gs->checkPermission($_SESSION["accountID"], "dashboardModTools");
if(!$perms){
	//Printing error
	$errorDesc = $dl->getLocalizedString("errorNoPerm");
	exit($dl->printBox('<h1>'.$dl->getLocalizedString("errorGeneric")."</h1>
					<p>$errorDesc</p>
					<a class='btn btn-primary btn-block' href='".$_SERVER["REQUEST_URI"]."'>".$dl->getLocalizedString("tryAgainBTN")."</a>","cron"));
}
//Gettings users
$deleted = 0;

$query = $db->prepare("SELECT userID, userName, extID, lastPlayed FROM users WHERE NOT extID REGEXP '^[0-9]+$' AND lastPlayed < :time");
$query->execute([':time' => time() - 604800]);
$users = $query->fetchAll();
foreach($users as $user){
	$query = $db->prepare("SELECT count(*) FROM levels WHERE userID = :userID");
	$query->execute([':userID' => $user["userID"]]);
	$count = $query->fetchColumn();
	$query = $db->prepare("SELECT count(*) FROM comments WHERE userID = :userID");
	$query->execute([':userID' => $user["userID"]]);
	$count += $query->fetchColumn();
	if($count == 0){
		//Deleting user
		$query = $db->prepare("DELETE FROM users WHERE userID = :userID");
		$query->execute([':userID' => $user["userID"]]);
		$deleted++;
	}
}
$query = $db->prepare("SELECT * FROM `accounts` WHERE `isActive` = 0");
$query->execute();
$accounts = $query->fetchAll();
foreach($accounts as $account){
		//Deleting user
		$query = $db->prepare("DELETE FROM accounts WHERE accountID = :accountID");
		$query->execute([':accountID' => $account["accountID"]]);
		$deleted++;
}

if($deleted > 1){
	$dl->printBox("<h1>".$dl->getLocalizedString("deleteUnused")."</h1>
					<p>".sprintf($dl->getLocalizedString("deletedP"), $deleted)."</p>","cron");
}elseif($deleted == 1){
	$dl->printBox("<h1>".$dl->getLocalizedString("deleteUnused")."</h1>
					<p>".$dl->getLocalizedString("deletedS")."</p>","cron");
}elseif($deleted == 0){
	$dl->printBox("<h1>".$dl->getLocalizedString("deleteUnused")."</h1>
					<p>".$dl->getLocalizedString("noDeleted")."</p>","cron");
}
?>
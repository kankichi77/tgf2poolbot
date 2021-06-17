<?php
// Only run this from CLI
if (!isset($argv)) exit;

require "env.php";
require "db.php";
require "telegram.php";
require "f2pool.php";

$sleep_interval = 5;
$db = new f2poolbot_db();
$tg = new Telegram();
$pool = new F2Pool();

if (isset($argv[1])) {
  if ($argv[1] == "on") {
    $db->turnBatchSwitchOn();
  } elseif ($argv[1] == "off") {
    $db->turnBatchSwitchOff();
  }
} else {
  while($db->isBatchSwitchOn()) {
    // MAIN LOOP
    
    // get all f2_username where auto_monitor == on for that user
	$userIds = Array();
	$usernames = array();	  
	$userIds = $db->getAutoMonitorModeOnUserIds();
	foreach($userIds as $uid) {
		$usernames[] = $db->getF2Username($uid);
	}
    
    // for each f2_username get next_batch_run_time
	foreach ($userIds as $uid) {
		$f2_username = $db->getF2Username($uid);
		if ($f2_username != "") {
			//echo "\nuser id: " . $uid . "\n";
			//echo "f2 username: " . $f2_username . "\n";
			$next_run_time = $db->getNextBatchRunTime($uid);
			//echo "now: " . time() . "\n";
			//echo "next: " . $next_run_time . "\n";

			// if current_time > next_batch_run_time then run the command
			if ($next_run_time <= time()) {
				$tg = new Telegram();
				//echo "chat id: " . $db->getChatId($uid) . "\n";
				$tg->setChatId($db->getChatId($uid));
				// run command
				$pool->setUsername($f2_username);
				if ($pool->fetchPoolInfo()) {
					$m = "[Auto Monitor]\n";
					$m .= $pool->getStatusSummaryMessage();
					$tg->returnTgMessage($m);
					echo "Telegram message sent.\n";
				} else {
					$m = "Error retreiving Pool Information.";
					$tg->returnTgMessage($m);
					echo $m . "\n";
				}
				// set next_batch_run_time for that user
				$db->setNextBatchRunTime($uid);
			}
		}
	}

	//var_dump($userIds);
	//var_dump($usernames);
    sleep($sleep_interval);
  }
}
?>

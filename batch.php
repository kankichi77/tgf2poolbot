<?php
// Only run this from CLI
if (!isset($argv)) exit;

require "env.php";
require "db.php";
require "telegram.php";
require "f2pool.php";

$sleep_interval = 10;
$db = new f2poolbot_db();
$tg = new Telegram();
$pool = new F2Pool();
$ERROR = [
	"CANNOT_RETRIEVE_POOL_INFO"	=>	"Error: Couldn't retreive Pool Information",
	"F2_USERNAME_UNDEFINED"		=>	"Error: F2 Username is not defined",
];

if (isset($argv[1])) {
  if ($argv[1] == "on") {
    $db->turnBatchSwitchOn();
  } elseif ($argv[1] == "off") {
    $db->turnBatchSwitchOff();
  } elseif ($argv[1] == "status") {
	  $m = "\n";
	  $m .= "Batch Switch : ";
	  if ($db->isBatchSwitchOn()) {
		  $m .= "on";
	  } else {
		  $m .= "off";
	  }
	  $m .= "\n";
	  $m .= "Debug Mode : ";
	  if ($db->isDebugModeOn()) {
		  $m .= "on";
	  } else {
		  $m .= "off";
	  }
	  $m .= "\n";
	  $m .= "Batch Running? ";
	  if ($db->isBatchRunning()) {
		  $m .= "yes";
	  } else {
		  $m .= "no";
	  }
	  $m .= "\n\n";
	  echo $m;
  } elseif ($argv[1] == "debug-on") {
	  if($ENV["ENV"] == "DEV") {
	  	$db->setDebugModeOn();
	  } else {
		$db->setDebugModeOff();
	  }
  } elseif ($argv[1] == "debug-off") {
	  $db->setDebugModeOff();
  }
} else {
  while($db->isBatchSwitchOn()) {
	// MAIN LOOP
	$db->setBatchRunningStatusOn();
	$userIds = $db->getOfflineAlertOnUserIds();
	
	foreach ($userIds as $uid) {
		$f2_username = $db->getF2Username($uid);
		if ($f2_username != "") {
			$pool->setUsername($f2_username);
			if ($pool->fetchPoolInfo()) {
				if($pool->numOfOfflineWorkers()
					|| $db->isDebugModeOn()
				) {
					$tg->setChatId($db->getChatId($uid));
					$tg->returnTgMessage($pool->getOfflineAlertMessage());
					$db->setOfflineAlertOff($uid);
				}
			} else {
				$tg->returnTgMessage($ERROR["CANNOT_RETRIEVE_POOL_INFO"]);
			}
		} else {
			$tg->returnTgMessage($ERROR["F2_USERNAME_UNDEFINED"]);
		}
	}	


    // get all f2_username where auto_monitor == on for that user
	//$userIds = Array();
	  //$usernames = array();	  
	$userIds = $db->getAutoMonitorModeOnUserIds();
	//foreach($userIds as $uid) {
	//	$usernames[] = $db->getF2Username($uid);
	//}
    
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
				//$tg = new Telegram();
				//echo "chat id: " . $db->getChatId($uid) . "\n";
				$tg->setChatId($db->getChatId($uid));
				// run command
				$pool->setUsername($f2_username);
				if ($pool->fetchPoolInfo()) {
					$m = "[Auto Monitor]\n";
					$m .= $pool->getStatusSummaryMessage();
					$tg->returnTgMessage($m);
					//echo "Telegram message sent.\n";
				} else {
					$m = "Error retreiving Pool Information.";
					$tg->returnTgMessage($m);
					//echo $m . "\n";
				}
				// set next_batch_run_time for that user
				$db->setNextBatchRunTime($uid);
			} else {
				//echo "next: in " . floor(($next_run_time - time())/60) . " min ";
			       	//echo ($next_run_time - time()) % 60 . " sec\n\n";
			}
		}
	}

	//var_dump($userIds);
	//var_dump($usernames);
    sleep($sleep_interval);
  }
  $db->setBatchRunningStatusOff();
}
?>

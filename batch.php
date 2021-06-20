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
  } elseif ($argv[1] == "prod"
	    || $argv[1] == "dev"
  ) {
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
	  	$userIds = $db->getAutoMonitorModeOnUserIds();
  	  	// for each f2_username get next_batch_run_time
	  	foreach ($userIds as $uid) {
			$f2_username = $db->getF2Username($uid);
			if ($f2_username != "") {
				$next_run_time = $db->getNextBatchRunTime($uid);
				// if current_time > next_batch_run_time then run the command
				if ($next_run_time <= time()) {
					$tg->setChatId($db->getChatId($uid));
					// run command
					$pool->setUsername($f2_username);
					if ($pool->fetchPoolInfo()) {
						$m = "[Auto Monitor]\n";
						$m .= $pool->getStatusSummaryMessage();
						$tg->returnTgMessage($m);
					} else {
						$m = "Error retreiving Pool Information.";
						$tg->returnTgMessage($m);
					}
					// set next_batch_run_time for that user
					$db->setNextBatchRunTime($uid);
				} else {
				}
			}
	  	}
		sleep($sleep_interval);
	  }
	  $db->setBatchRunningStatusOff();
  }
} else {
}
?>

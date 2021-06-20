<?php
require "env.php";
require "db.php";
require "telegram.php";
require "f2pool.php";

$arg = "";
$ts = date('Y/m/d H:i:s');
$redis = new Redis();
$db = new f2poolbot_db();
$tg = new Telegram();
$pool = new F2Pool();
$f2pool_path = $pool->getApiPath();
$f2pool_path = $pool->getApiPath();
$telegram_path = $tg->getTelegramPath();
$m = "";

$LOCAL_ENV = [
	"BATCH_INTERVAL_MULTIPLE"	=>	60,	// in Minutes 
	"BATCH_INTERVAL_UNIT"		=>	"minute(s)",
];

$commands = [
	"status" => "/status",
	"status_workers" => "/status_workers",
	"help" => "/help",
	"start" => "/start",
		"enable_automonitor" => "/enable_automonitor",
		"disable_automonitor" => "/disable_automonitor",
		"show_automonitor_status" => "/show_automonitor_status",
		"set_F2_Username" => "/set_pool_username",
		"set_batch_interval" => "/set_automonitor_interval",
		"get_batch_interval" => "/show_automonitor_interval",
		"enable_offlinealert" => "/enable_offlinealert",
		"disable_offlinealert" => "/disable_offlinealert",
		"show_next_batch_run_timedate" => "/show_automonitor_nextruntime",
		"is_batch_running" => "/is_batch_running",
];

// DEV
if (isDevMode()) {
	$commands += [
	];
}

$ERROR = [
	"INVALID_BATCH_INTERVAL"	=>	"Invalid input.  Please try again and enter number of " . $LOCAL_ENV["BATCH_INTERVAL_UNIT"],
];

try {
  $db->addLog();    
} catch( Exception $e ){
  echo $e->getMessage();
} 

$update = json_decode(file_get_contents("php://input"), TRUE);

if ($update) {
  $chatId = $update["message"]["chat"]["id"];
  $message = $update["message"]["text"];
  $username = $update["message"]["from"]["username"];
  $user_id = $update["message"]["from"]["id"];

  $tg->setMessageText($message);
  $tg->setUserId($user_id);
  $tg->setUsername($username);
  $tg->setChatId($chatId);
  $db->setTelegramUsername($user_id, $username);
  $db->setChatId($user_id, $chatId);

  if (isCommand($message, $commands, "enable_offlinealert")) {
	  $db->setOfflineAlertOn($user_id);
	  $tg->returnTgMessage("Offline Alert Enabled");

  } elseif (isCommand($message, $commands, "disable_offlinealert")) {
	  $db->setOfflineAlertOff($user_id);
	  $tg->returnTgMessage("Offline Alert Disabled");

  } elseif (isCommand($message, $commands, "show_automonitor_status")) {
	  $m = "Auto Monitor is ";
	  if ($db->isAutoMonitorModeOn($user_id)) {
		  $m .= "Enabled ";
	  } else {
		  $m .= "Disabled";
	  }
	  $tg->returnTgMessage($m);

  } elseif (isCommand($message, $commands, "is_batch_running")) {
          $m = "Batch Program is ";
          if ($db->isBatchRunning()) {
                  $m .= "On";
          } else {
		  $m .= "Off";
	  }
          $tg->returnTgMessage($m);

  } elseif (isCommand($message, $commands, "show_next_batch_run_timedate")) {
	  $m = "Next Auto Monitor Run time in ";
	  $m .= $db->getNextBatchRunTimeDate($user_id);
	  $tg->returnTgMessage($m);

  } elseif (isCommand($message, $commands, "get_batch_interval")) {
	 $m = "Current Auto Monitor interval is: ";
	 $m .= $db->getBatchRunInterval($user_id)/$LOCAL_ENV["BATCH_INTERVAL_MULTIPLE"];
	 $m .= " " . $LOCAL_ENV["BATCH_INTERVAL_UNIT"];
    $tg->returnTgMessage($m);

  } elseif (isCommand($message, $commands, "set_batch_interval")) {
    $interval = intval(substr($message, strlen($commands["set_batch_interval"])+1));
    if ($interval) {
      $db->setBatchRunInterval($user_id, $interval * $LOCAL_ENV["BATCH_INTERVAL_MULTIPLE"]);
      $db->setNextBatchRunTime($user_id);
      $m = "Auto Monitor interval set to " . $interval . " " . $LOCAL_ENV["BATCH_INTERVAL_UNIT"];
    } else {
      $m = $ERROR["INVALID_BATCH_INTERVAL"];
    }
    $tg->returnTgMessage($m);

  } elseif (isCommand($message, $commands, "set_F2_Username")) {
    $f2pool_username = substr($message, strlen($commands["set_F2_Username"])+1);
    $db->setF2Username($tg->getUserId(), $f2pool_username);
    returnTgMessage("F2 Pool username set to: " . $f2pool_username);

  } elseif (isCommand($message, $commands, "status")) {
    if (isCommand($message, $commands, "status_workers")) {
      $f2pool_username = substr($message, strlen($commands["status_workers"])+1);
    } else {
      $f2pool_username = substr($message, strlen($commands["status"])+1);
    }
    $f2pool_username = strtok($f2pool_username, " ");
    if ($f2pool_username == "" || $f2pool_username == "username") {
      if ($db->isF2UsernameSet($tg->getUserId())) {
        $f2pool_username = $db->getF2Username($tg->getUserId());
      } else {
        $tg->returnTgMessage("Please specify the F2 Pool username");
        exit;
      }
    }

    $pool->setUsername($f2pool_username);
    if ($pool->fetchPoolInfo()) {
      if (isCommand($message, $commands, "status_workers")) {
	    $reply = $pool->getStatusDetailedMessage();
      } else {
	    $reply = $pool->getStatusSummaryMessage();
      }
    } else {
      $reply = "Error retrieving pool data.";
    }
    $tg->returnTgMessage($reply);

  } elseif (isCommand($message, $commands, "enable_automonitor")) {
    if ($db->isF2UsernameSet($user_id)) {	  
      $db->setAutoMonitorModeOn($user_id);
      if (!$db->isBatchRunIntervalSet($user_id)) {
	      $db->setBatchRunInterval($user_id, 0);  // default interval
      }
      $db->setNextBatchRunTime($user_id);
      $m = "Auto Monitor Mode enabled with interval at ";
      $m .= floor($db->getBatchRunInterval($user_id)/$LOCAL_ENV["BATCH_INTERVAL_MULTIPLE"]);
      $m .= " " . $LOCAL_ENV["BATCH_INTERVAL_UNIT"];
      $tg->returnTgMessage($m);
    } else {
      $tg->returnTgMessage("Please set your F2 Pool username");
    }

  } elseif (isCommand($message, $commands, "disable_automonitor")) {
    $db->setAutoMonitorModeOff($user_id);
    returnTgMessage("Auto Monitor Mode disabled.");
  
  } elseif (isCommand($message, $commands, "help")) {
    $reply = $ENV["TELEGRAM_BOT_NAME"] . "\n";
    $reply .= getHelpMessage();
    $tg->returnTgMessage($reply);
  } elseif (isCommand($message, $commands, "start")) {
    $reply = $ENV["TELEGRAM_BOT_NAME"] . "\n";
    $reply .= getHelpMessage();
    $tg->returnTgMessage($reply);
  } else {
    // Do nothing	  
  }
} elseif (0) {
  // DEBUG
  echo "<html><head><title></title></head><body>";	
  echo $db->getLogCount() . "<br>";
  echo $db->getPrevLastAccessed() . "<br>";
  echo $db->getLastAccessed() . "<br>";
  echo "</body></html>";
}

function getHelpMessage() {
$m = "
This bot will retrieve stats on your miner at the F2 Pool

[BASIC]
/help - Show this help
/status - Show status for a F2 username (if F2 username already set)
/status_<username> - Show status for a F2 username
/status_workers - Show detailed status for each worker (if F2 username already set) in the following format:
#) worker_name - 15min_hashrate - 24hour_hashrate
/status_workers_<username> - Show detailed status for each worker for a F2 user

[AUTO MONITOR]
/enable_automonitor - Turn on Auto Monitor Mode
/disable_automonitor - Turn off Auto Monitor Mode
/show_automonitor_status - Shows the current Auto Monitor Status
/set_pool_username - Set your F2 Pool username
/set_automonitor_interval - Set the Auto Monitor interval in minutes
/show_automonitor_interval - Shows the current Auto Monitor interval
/show_automonitor_nextruntime - Shows when the next Auto Monitor will run

[OFFLINE ALERT]
/enable_offlinealert - Turn on the Offline Alert
/disable_offlinealert - Turn off the Offline Alert

Note: Please keep your username private.  This bot does not use your F2 Pool information for any other purposes other than to provide the functionalities above.
";
return $m;
}

function isCommand($m, $commands, $i) {
  return strpos($m, $commands[$i]) === 0;
}

function returnTgMessage($m) {
  global $telegram_path, $chatId;
  file_get_contents($telegram_path."/sendmessage?chat_id=".$chatId."&text=".urlencode($m));
}

function toTH($h,$d) {
  if ($h == "" || floatval($h) == 0) {
    return 0;
  }
  return floor(floatval($h)/10000000000)/100 . " TH/s";
}

function getNetHR($grossHR, $rejectedH) {
  $grossHR = floatval($grossHR);
  $rejectedH = floatval($rejectedH);
  return $grossHR - $rejectedH;
}

function isDevMode() {
	global $ENV;
	return $ENV["ENV"] == "DEV";
}
?>

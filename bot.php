<?php
require_once "env.php";
require_once "db.php";

$token = $TELEGRAM_BOT_TOKEN;
$telegram_path = "https://api.telegram.org/bot" . $token;
$arg = "";
$ts = date('Y/m/d H:i:s');
$f2pool_path = "https://api.f2pool.com/bitcoin/";

$redis = new Redis();
$db = new f2poolbot_db();

$commands = [
	"status" => "/status",
	"status_workers" => "/status_workers",
	"help" => "/help",
	"start" => "/start",
	"enable_automonitor" => "/enable_automonitor",
	"disable_automonitor" => "/disable_automonitor",
];

try {
  $redis->connect("localhost");
  $redis->select(2);
  if($redis->exists("log_count")) {
    $redis->incr("log_count");
  } else {
    $redis->set("log_count", 1);
  }
  if (!$redis->exists("last_accessed")) {
    $redis->set("last_accessed", $ts);
  }
  $redis->set("previous_last_accessed", $redis->get("last_accessed"));
  $redis->set("last_accessed", $ts);
} catch( Exception $e ){
  echo $e->getMessage();
} 

$update = json_decode(file_get_contents("php://input"), TRUE);

if ($update) {
  $chatId = $update["message"]["chat"]["id"];
  $message = $update["message"]["text"];
  $username = $update["message"]["from"]["username"];
  $user_id = $update["message"]["from"]["id"];

  $db->setTelegramUsername($user_id, $username);
  
  if (isCommand($message, $commands, "status")) {
    if (isCommand($message, $commands, "status_workers")) {
      $f2pool_username = substr($message, strlen($commands["status_workers"])+1);
    } else {
      $f2pool_username = substr($message, strlen($commands["status"])+1);
    }
    $f2pool_username = strtok($f2pool_username, " ");
    if ($f2pool_username == "" || $f2pool_username == "username") {
      returnTgMessage("Please specify the username");
      exit;
    }
    $path = $f2pool_path . $f2pool_username;
    $f2pool_info = json_decode(file_get_contents($path), TRUE);
    if (isValidF2Username($f2pool_info)) {
      $reply = $f2pool_info["worker_length_online"] . "/" . $f2pool_info["worker_length"];
      $reply .= " worker(s) online\n";
      $reply .= "Total Current Hashrate: " . toTH($f2pool_info["hashrate"],2) . "\n";
      $reply .= "Total 24h hashrate: " . toTH($f2pool_info["hashes_last_day"]/(24*60*60),2) . "\n";

      if (isCommand($message, $commands, "status_workers")) {
        $reply .= "\n";
        $reply .= "Workers:\n";
        $counter = 0;
        foreach ($f2pool_info["workers"] as $worker) {
	  $counter++;      
	  $reply .= $counter . ") " . $worker[0] . " - " . toTH($worker[1],2) . " - ";
	  $reply .= toTH($worker[4]/(24*60*60),2);
        }
      }
    } else {
      $reply = "Error retrieving pool data.";
    }
    returnTgMessage($reply);
  } elseif (isCommand($message, $commands, "enable_automonitor")) {
    if ($db->isF2UsernameSet($user_id)) {	  
      //$db->setAutoMonitorMode($user_id, 1);
      returnTgMessage("Auto Monitor Mode enabled.");
    } else {
      returnTgMessage("BOO");
    }
  } elseif (isCommand($message, $commands, "disable_automonitor")) {
    $db->setAutoMonitorMode($user_id, 0);
    returnTgMessage("Auto Monitor Mode disabled.");
  } elseif (isCommand($message, $commands, "help") ||
            isCommand($message, $commands, "start")) {
    $reply = $TELEGRAM_BOT_NAME . "\n";
    $reply .= "This bot will retrieve stats on your miner at the F2 Pool\n";
    $reply .= "\n";
    $reply .= "/status_<username>\n";
    $reply .= "Replace <username> with your F2 Pool username. This will show you a summary of your current mining stats.\n";
    $reply .= "\n";
    $reply .= "/status_workers_<username>\n";
    $reply .= "Replace <username> with your F2 Pool username. This will show you a summary along with stats for each of your workers in the following format:\n";
    $reply .= "worker_name - 15min_hashrate - 24hour_hashrate\n";
    $reply .= "\n";
    $reply .= "Note: Please keep your username private.  This bot does not store your username by default.";
    returnTgMessage($reply);
  } else {
    // Do nothing	  
    // returnTgMessage($message);
  }
} elseif (1) {
  // DEBUG
  echo "<html><head><title></title></head><body>";	
  echo $redis->get("log_count") . "<br>";
  echo $redis->get("previous_last_accessed") . "<br>";
  echo $redis->get("last_accessed") . "<br>";
  echo "</body></html>";
}

function isValidF2Username($pool_info) {
  $result = false;
  if ($pool_info["worker_length"] != "0" && $pool_info["worker_length"] != "") {
    $result = true;
  }
  return $result;
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
  return floor(floatval($h)/10000000000)/100;
}

function getNetHR($grossHR, $rejectedH) {
  $grossHR = floatval($grossHR);
  $rejectedH = floatval($rejectedH);
  return $grossHR - $rejectedH;
}
?>

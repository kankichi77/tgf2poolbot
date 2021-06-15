<?php
require "env.php";
$token = $telegram_bot_token;
$telegram_path = "https://api.telegram.org/bot" . $token;
$arg = "";
$ts = date('Y/m/d H:i:s');
$redis = new Redis();

//$f2pool_username = "kanhne";
$f2pool_path = "https://api.f2pool.com/bitcoin/";

$commands = [
        "/hello",
	"/f2_online",
	"/help",
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

//  if (strpos($message, $commands[0]) === 0) {
  if (isCommand($message, $commands, 0)) {      
    //$arg = substr($message, strlen($command1)+1);
    returnTgMessage($message);	  
    //file_get_contents($telegram_path."/sendmessage?chat_id=".$chatId."&text=Hello ".$user_id);
    
  } elseif (isCommand($message, $commands, 1)) {	  
    $f2pool_username = substr($message, strlen($commands[1])+1);
    //returnTgMessage($f2pool_username);
  //}elseif (0) {
    $path = $f2pool_path . $f2pool_username;
    $f2pool_info = json_decode(file_get_contents($path), TRUE);
    if (isValidF2Username($f2pool_info)) {
      //$message = "F2 Pool Info retrieved";
      $message = $f2pool_info["worker_length_online"] . "/" . $f2pool_info["worker_length"];
      $message .= " worker(s) online\n";
      $message .= "Total Current Hashrate: " . toTH($f2pool_info["hashrate"],2) . "\n";
      $message .= "Total 24h hashrate: " . toTH($f2pool_info["hashes_last_hour"],2) . "\n";
      $message .= "\n";
      $counter = 0;
      foreach ($f2pool_info["workers"] as $worker) {
	$counter++;      
        $message .= $counter . ") " . $worker[0] . " - " . toTH($worker[1],2) . " - " . toTH($worker[2],2);
      }
      $message .= "";
    } else {
      $message = "Error";
    }
    returnTgMessage($message);
    //file_get_contents($telegram_path."/sendmessage?chat_id=".$chatId."&text=".$message);

  } elseif (isCommand($message, $commands, 2)) {
    $message = "This bot will help you retrieve stats on your miner at the F2 Pool\n";
    $message .= "\n";
    $message .= "/f2_online_<f2_username>\n";
    $message .= "Replace <username> with your F2 Pool username. This will show you your current online workers' stats in the following format:\n";
    $message .= "worker_name - last_1_hour_hashrate - last_24hour_hashrate\n";

    returnTgMessage($message);
  } else {
    if (strlen($message) > 12) $message = substr($message, 0, 12) . "...";
    returnTgMessage($message);
    //file_get_contents($telegram_path."/sendmessage?chat_id=".$chatId."&text=".$message);
  }
} elseif (1) {
  //echo "bot!";
  echo "<html><head><title></title></head><body>";	
  echo $redis->get("log_count") . "<br>";
  echo $redis->get("previous_last_accessed") . "<br>";
  echo $redis->get("last_accessed") . "<br>";
  echo "</body></html>";
}

function isValidF2Username($pool_info) {
  $result = false;
  if ($pool_info["worker_length"] != "0") {
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
  //return floatval($h)/1000000000000;	
  return round(floatval($h)/1000000000000,$d);
}
?>

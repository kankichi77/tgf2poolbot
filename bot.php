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
    $f2pool_username = substr($message, strlen($commands[1]));
    returnTgMessage($f2pool_username);

  }elseif (0) {
    $path = $f2pool_path . $f2pool_username;
    $f2pool_info = json_decode(file_get_contents($f2pool_path), TRUE);
    if ($f2pool_info) {
      //$message = "F2 Pool Info retrieved";
      $message = $f2pool_info["worker_length_online"] . " worker(s) online";
    } else {
      $message = "Error";
    }
    returnTgMessage($message);
    //file_get_contents($telegram_path."/sendmessage?chat_id=".$chatId."&text=".$message);
  
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

function isCommand($m, $commands, $i) {
  return strpos($m, $commands[$i]) === 0;
}

function returnTgMessage($m) {
  global $telegram_path, $chatId;
  file_get_contents($telegram_path."/sendmessage?chat_id=".$chatId."&text=".$m);
}
?>

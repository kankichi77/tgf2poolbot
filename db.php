<?php
require_once "env.php";

Class f2poolbot_db {
  private $db;
  private $logkey = "log_count";
  private $lastaccessedkey = "last_accessed";
  private $previouslastaccessedkey = "previous_last_accessed";
  private $KEYS = [
	  "batchSwitch" => "batchSwitch",
  ];
  private $LOCAL_ENV = [
	  "DEFAULT_BATCHRUNINTERVAL" => 600,	// 1 hour
	  "MIN_BATCHRUNINTERVAL" => 600,	// 1 hour
	  "MAX_BATCHRUNINTERVAL" => 86400,	// 24 hours
  ];

  public function __construct() {
    global $ENV;
    $this->db = new Redis();

    try {
      $this->db->connect("localhost");
      $this->db->select($ENV["REDIS_DB"]);
    } catch( Exception $e ){
      throw $e;
    }
  }

  public function turnBatchSwitchOn() {
    $this->set($this->KEYS["batchSwitch"], 1);
  }

  public function turnBatchSwitchOff() {
    $this->set($this->KEYS["batchSwitch"], 0);
  }

  public function isBatchSwitchOn() {
    return $this->get($this->KEYS["batchSwitch"]) == 1;
  }

  public function exists($key) {
    return $this->db->exists($key);
  }

  public function set($key, $value) {
    return $this->db->set($key, $value);
  }

  public function get($key) {
    if ($this->exists($key)) {
      return $this->db->get($key);
    } else {
      return "";
    }
  }

  public function incr($key) {
    return $this->db->incr($key);
  }

  public function addLog() {
    $ts = date('Y/m/d H:i:s');
    if ($this->exists($this->logkey)) {
      $this->incr($this->logkey);
    } else {
      $this->set($this->logkey, 1);
    }
    if (!$this->exists($this->lastaccessedkey)) {
      $this->set($this->lastaccessedkey,$ts);
    }
    $this->set($this->previouslastaccessedkey, $this->get($this->lastaccessedkey));
    $this->set($this->lastaccessedkey, $ts);
  }

  public function getLogCount() {
    return $this->get($this->logkey);
  }

  public function getLastAccessed() {
    return $this->get($this->lastaccessedkey);
  }

  public function getPrevLastAccessed() {
    return $this->get($this->previouslastaccessedkey);
  }

  public function setTelegramUsername($uid, $uname) {
    $this->db->set("username_for_userid_" . $uid, $uname);
  }

  public function getTelegramUsername($uid) {
    return $this->get("username_for_userid_" . $uid);
  }

  public function isAutoMonitorModeSet($uid) {
    $this->db->exists("automonitormode_for_userid_" . $uid);
  }

  public function setAutoMonitorModeOn($uid) {
    $this->set("automonitormode_for_userid_" . $uid, 1);
  }

  public function setAutoMonitorModeOff($uid) {
    $this->set("automonitormode_for_userid_" . $uid, 0);
  }

  public function getAutoMonitorMode($uid) {
    return $this->get("automonitormode_for_userid_" . $uid);
  }

  public function getAutoMonitorModeOnUserIds() {
    $result = Array();
    $keys = $this->db->keys("automonitormode_for_userid_*");
    foreach ($keys as $key) {
      if ($this->get($key)) $result[] = substr($key, strlen("automonitormode_for_userid_"));
    }
    return $result;
  }

  public function isF2UsernameSet($uid) {
    return $this->exists("f2username_for_userid_" . $uid);
  }

  public function setF2Username($uid, $f2_uname) {
    $this->db->set("f2username_for_userid_" . $uid, $f2_uname);
  }

  public function getF2Username($uid) {
    return $this->get("f2username_for_userid_" . $uid);
  }

  public function setBatchRunInterval($uid, $i) {
    if ($i == "" || 
	$i == 0 || 
	$i < $this->LOCAL_ENV["MIN_BATCHRUNINTERVAL"] ||
	$i > $this->LOCAL_ENV["MAX_BATCHRUNINTERVAL"]
    ) {
      $i = $this->LOCAL_ENV["DEFAULT_BATCHRUNINTERVAL"];
    }
    $this->set("automonitor_interval_for_userid_" . $uid, $i);
  }

  public function getBatchRunInterval($uid) {
    return $this->get("automonitor_interval_for_userid_" . $uid);
  }

  public function setNextBatchRunTime($uid) {
    $this->set("nextbatchruntime_for_userid_" . $uid, time() + $this->getBatchRunInterval($uid));
  }

  public function getNextBatchRunTime($uid) {
    return $this->get("nextbatchruntime_for_userid_" . $uid);
  }

  public function setChatId($uid, $chatId) {
    $this->set("chatid_for_userid_" . $uid, $chatId);
  }

  public function getChatId($uid) {
    return $this->get("chatid_for_userid_" . $uid);
  }
}
?>

<?php
require "env.php";

Class f2poolbot_db {
  private $db;
  private $logkey = "log_count";
  private $lastaccessedkey = "last_accessed";
  private $previouslastaccessedkey = "previous_last_accessed";

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

  public function exists($key) {
    return $this->db->exists($key);
  }

  public function set($key, $value) {
    return $this->db->set($key, $value);
  }

  public function get($key) {
    return $this->db->get($key);
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
    return $this->db->get("username_for_userid_" . $uid);
  }

  public function isAutoMonitorModeSet($uid) {
    $this->db->exists("automonitormode_for_userid_" . $uid);
  }

  public function setAutoMonitorMode($uid, $value) {
    $this->db->set("automonitormode_for_userid_" . $uid, $value);
  }

  public function getAutoMonitorMode($uid) {
    return $this->db->get("automonitormode_for_userid_" . $uid);
  }

  public function isF2UsernameSet($uid) {
    return $this->db->exisits("f2username_for_userid_" . $uid);
  }

  public function setF2Username($uid, $f2_uname) {
    $this->db->set("f2username_for_userid_" . $uid, $uname);
  }

  public function getF2Username($uid) {
    return $this->db->get("f2username_for_userid_" . $uid);
  }
}
?>

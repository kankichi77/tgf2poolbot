<?php
require "env.php";

Class f2poolbot_db {
  private $db;

  public function __construct() {
    global $CONFIG;
    $this->db = new Redis();

    try {
      $this->db->connect("localhost");
      $this->db->select($CONFIG["REDIS_DB"]);
    } catch( Exception $e ){
      throw $e;
    }
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

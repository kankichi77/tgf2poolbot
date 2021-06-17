<?php
require_once "env.php";

Class Telegram {
  private $TELEGRAM_API_PATH;
  private $userId;
  private $username;
  private $chatId;
  private $messageText;

  public function __construct() {
    global $ENV;
    $this->TELEGRAM_API_PATH = "https://api.telegram.org/bot" . $ENV["TELEGRAM_BOT_TOKEN"];
  }

  public function returnTgMessage($m) {
    if ($this->chatId) {
      file_get_contents($this->TELEGRAM_API_PATH . "/sendmessage?chat_id=" . $this->chatId . "&text=" . urlencode($m));
    }
  }

  public function getTelegramPath() {
    return $this->TELEGRAM_API_PATH;
  }

  public function setUserId($uid) {
    $this->userId = $uid;
  }

  public function getUserId() {
    return $this->userId;
  }

  public function setUsername($un) {
    $this->username = $un;
  }
  
  public function getUsername() {
    return $this->username;
  }

  public function setChatId($c) {
    $this->chatId = $c;
  }
  
  public function getChatId() {
    return $this->chatId;
  }

  public function setMessageText($m) {
    $this->messageText = $m;
  }

  public function getMessageText() {
    return $this->messageText;
  }
}
?>

<?php
require_once "env.php";

Class F2Pool {
	private $api_path = "https://api.f2pool.com/bitcoin/";
	private $username;
	private $pool_info;

  public function __construct() {
	  global $ENV;
	  $this->api_path = $this->api_path;
  }

  public function getApiPath() {
	  return $this->api_path;
  }

  public function setUsername($u) {
	  $this->username = $u;
	  $this->api_path = $this->api_path . $u;
  }

  public function getUsername() {
	  if ($this->username) return $this->username;
	  return "";
  }

  public function fetchPoolInfo(){
	$this->pool_info = json_decode(file_get_contents($this->api_path), TRUE);
  	return $this->isValidPoolInfo();
  }

  private function isValidPoolInfo() {
  	$result = false;
	if (isset($this->pool_info["worker_length"]) &&
		  $this->pool_info["worker_length"] != "0" && 
		  $this->pool_info["worker_length"] != ""
	   ) {
    		$result = true;
  	}
  	return $result;
  }

  public function getStatusSummaryMessage() {
	  return $this->makeStatusSummaryMessage();
  }

  public function getStatusDetailedMessage() {
	  $m = $this->makeStatusSummaryMessage();
	  $m .= $this->makeWorkersDetailMessage();
	  return $m;
  }

  private function makeStatusSummaryMessage() {
  	$m = $this->pool_info["worker_length_online"] . "/" . $this->pool_info["worker_length"];
	$m .= " worker(s) online\n";
	$m .= "Total Current Hashrate: " . $this->toTH($this->pool_info["hashrate"],2) . "\n";
	$m .= "Total 24h hashrate: " . $this->toTH($this->pool_info["hashes_last_day"]/(24*60*60),2) . "\n";
	return $m;
  }

  private function makeWorkersDetailMessage() {
  	$m = "\n";
        $m .= "Workers:\n";
        $counter = 0;
        foreach ($this->pool_info["workers"] as $worker) {
          $counter++;
          $m .= $counter . ") " . $worker[0] . " - " . $this->toTH($worker[1],2) . " - ";
          $m .= $this->toTH($worker[4]/(24*60*60),2);
        }
	return $m;
  }

  private function toTH($h,$d) {
  	if ($h == "" || floatval($h) == 0) {
    		return 0;
  	}
  	return floor(floatval($h)/10000000000)/100 . " TH/s";
  }
}
?>

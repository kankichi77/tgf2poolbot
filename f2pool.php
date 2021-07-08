<?php
require_once "env.php";

Class F2Pool {
	//private $default_api_path = "https://api.f2pool.com/bitcoin/";
	private $api_path;
	private $username;
	private $pool_info;

  public function __construct() {
	  global $ENV;
	  $this->username = "";
	  $this->api_path = "";
	  //$this->api_path = $this->default_api_path;
	  $this->pool_info = Array();
  }

  public function getPoolInfo($p) {
	if (isset($this->pool_info) && $p != "") {
		//return $this->pool_info[$p];
		return $this->pool_info[$this->username][$p];
	} else {
		return NULL;
	}
  }

  public function getApiPath() {
	  return $this->api_path;
  }

  public function setApiPath() {
	  //$this->api_path = "https://api.f2pool.com/bitcoin/" . $this->username;
	  $this->api_path = "https://api.f2pool.com/bitcoin/" . $this->username . "?multi_account=" . $this->username;
  }

  public function isApiPathSet() {
          return ($this->username != "")
                  && ($this->api_path != "");
  }

  public function setUsername($u) {
	  $this->username = $u;
	  $this->setApiPath();
	  //$this->api_path = $this->default_api_path . $u;
  }

  public function getUsername() {
	  if ($this->username) return $this->username;
	  return "";
  }

  public function fetchPoolInfo(){
	if ($this->isApiPathSet()) {
		$this->pool_info = json_decode(file_get_contents($this->getApiPath()), TRUE);
  		return $this->isValidPoolInfo();
	} else {
		return NULL;
	}
  }

  private function isValidPoolInfo() {
  	$result = false;
	if (isset($this->pool_info) && 
	    //isset($this->getPoolInfo("worker_length")) &&
	    $this->getPoolInfo("worker_length") != "0" && 
	    $this->getPoolInfo("worker_length") != ""
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

  public function numOfWorkers() {
	  if ($this->isValidPoolInfo()) {
		  return intval($this->getPoolInfo("worker_length"));
	  } else {
		  return 0;
	  }
  }

  public function numOfOnlineWorkers() {
	  if ($this->isValidPoolInfo()) {
		  return intval($this->getPoolInfo("worker_length_online"));
	  } else {
		  return 0;
	  }
  }

  public function numOfOfflineWorkers() {
	  return $this->numOfWorkers() - $this->numOfOnlineWorkers();
  }

  public function getOfflineAlertMessage() {
	  return $this->makeOfflineAlertMessage();
  }

  private function makeOfflineAlertMessage() {
	  $m = $this->numOfOfflineWorkers() . " WORKER(S) OFFLINE";
	  return $m;
  }

  private function makeStatusSummaryMessage() {
  	$m = $this->getPoolInfo("worker_length_online") . "/" . $this->getPoolInfo("worker_length");
	$m .= " worker(s) online\n";
	$m .= "Total Current Hashrate: " . $this->toTH($this->getPoolInfo("hashrate"),2) . "\n";
	$m .= "Total 24h hashrate: " . $this->toTH($this->getPoolInfo("hashes_last_day")/(24*60*60),2) . "\n";
	$m .= "Balance: " . $this->toBTC($this->getPoolInfo("fixed_balance")) . "\n";
	$m .= "Yesterday's Revenue: " . $this->toBTC($this->getPoolInfo("today_paid")) . "\n"; 
	$m .= "Today's Estimated Revenue: " . $this->toBTC($this->getPoolInfo("value_today")) . "\n"; 
	return $m;
  }

  private function makeWorkersDetailMessage() {
  	$m = "\n";
        $m .= "Workers:\n";
        $counter = 0;
        foreach ($this->getPoolInfo("workers") as $worker) {
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

  private function toBTC($str) {
	  // takes a string of format 0.123456789123... and returns first 10 chars so it's 0.12345678
	  return substr($str, 0, 10);
  } 
}
?>

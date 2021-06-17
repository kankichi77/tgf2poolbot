<?php
require "env.php";

Class F2Pool {
  private $api_path = "https://api.f2pool.com/bitcoin/";

  public function __construct() {
    global $ENV;
  }

  public function getApiPath() {
    return $this->api_path;
  }
}
?>

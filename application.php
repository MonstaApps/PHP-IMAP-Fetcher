#!/usr/bin/php -q
<?php

require_once("config.php");
require_once("class.php");
require_once("mimeDecode.php");

set_time_limit(600);
ini_set('max_execution_time',600);

$mysql = mysql_connect($mysql_host, $mysql_user, $mysql_pass);
$db_selected = mysql_select_db($mysql_db, $mysql);

mysql_set_charset('utf8',$mysql);

/* Pipe */
if ($grab_type == "pipe") {

  $source = "";
  $fd = fopen("php://stdin","r");
  while(!feof($fd)) {
    $source .= fread($fd,1024);
  }

  $uniqid = generateId(20).date("U");
  $emailMessage = new EmailObject($mysql,$uniqid,$source,$file_store);
  $emailMessage->readEmail();
}

/* Fetch */
if ($grab_type == "fetch") {
 
  $inbox = @imap_open("{".$imap_host.$imap_flags."}INBOX",$imap_user,$imap_pass);
 
  if ($inbox) {
    $emails = imap_search($inbox,"ALL");
    
    if ($emails) {
      rsort($emails);

      if ($emails) {
        foreach($emails AS $n) {
          $source = imap_fetchbody($inbox, $n, "");
          $uniqid = generateId(20).date("U");
          $emailMessage = new EmailObject($mysql,$uniqid,$source,$file_store);
          $emailMessage->readEmail();
          imap_delete($inbox, $n);
        }
        imap_expunge($inbox);
      }
    }
    /* imap_errors() is called to supress PHP errors, such as when a mailbox is empty */
    $errors = imap_errors();
    imap_close($inbox);
  }
}

function generateId($n) {

  mt_srand((double)microtime()*1000000);

  $id = "";
  while(strlen($id)<$n){
    switch(mt_rand(1,3)){
      case 1: $id.=chr(mt_rand(48,57)); break;  // 0-9
      case 2: $id.=chr(mt_rand(65,90)); break;  // A-Z
      case 3: $id.=chr(mt_rand(97,122)); break; // a-z
    }
  }
	
  return $id;
}

mysql_close($mysql);

<?php

require_once 'struct.php';

/*
** Send the command USER or NICK, with the $username[$count]
*/
function send_username($struct, $nick)
{
  static $count = 0;
  $username = array(
		    'regis',
		    'regis_robert',
		    'reglisse',
		    'regisrobert'
		    );
  $hostname = 'sbrk.org';
  $servername = 'sbrk.org';
  $real_name = 'regis';
  if ($nick == false)
    $command = "USER " . $username[$count] . " $hostname $servername :$real_name\r\n";
  else
    $command = "NICK " . $username[$count] . "\r\n";
  if (socket_write($struct->sock, $command) === FALSE) {
    echo 'Error while sending command "USER/NICK"';
  }
  $struct->nick = $username[$count];
  $count++;
}

function user_cmd($struct)
{
  if (strstr($struct->full_cmd, "Checking Ident") !== False)
    send_username($struct, true);
}

function nickname_used($struct)
{
  send_username($struct, true);
}

function bot_ready($struct)
{
  $chan = '##testysdsd';
  $join_cmd = "JOIN $chan\r\n";
  $struct->chans[$chan] = new Chan;
  $struct->chans[$chan]->name = $chan;
  $struct->chans[$chan]->is_op = false;
  $struct->chans[$chan]->members = array();
  socket_write($struct->sock, $join_cmd);
}


/*
** When someone joins a chan, add him into the list of members
*/
function join_cmd($struct)
{
  $array = explode(" ", $struct->full_cmd);
  $name = substr($array[0], 1, strpos($array[0], "!") - 1) ;
  $struct->chans[$array[2]]->members[$name] = false;
}



/*
** Add users from the /names command
** in the list of users in the chan
*/

function add_user_list($struct)
{
  $array = explode(" ", $struct->full_cmd);
  $chan = $array[4];
  $members_list = substr($struct->full_cmd, strrpos($struct->full_cmd, ":") + 1);
  $member_array = explode(" ", $members_list);
  foreach ($member_array as $member) {
    $op = $member[0] == '@' ? true : false;
    if ($op == true)
      $member = substr($member, 1);
    if ($member == $array[2]) {
      if ($op == true)
	$struct->chans[$chan]->is_op = true;
    }
    else {
      if (!in_array($member, $struct->chans[$chan]->members)) {
	$struct->chans[$chan]->members[$member] = $op;
      }
    }
  }
}


/*
** Someome left a chan, so we remove him
** from the channel members list
*/

function part_cmd($struct)
{
  $array = explode(" ", $struct->full_cmd);
  $name = substr($array[0], 1, strpos($array[0], "!") - 1);
  unset($struct->chans[$array[2]]->members[$name]);
}


/*
** When someone quit, we have to remove him/her
** from all the chan list he is
*/

function quit_cmd($struct)
{
  $array = explode(" ", $struct->full_cmd);
  $name = substr($array[0], 1, strpos($array[0], "!") - 1);
  foreach ($struct->chans as $name_chan => $chan) {
    if (isset($chan->members[$name])) {
      unset($chan->members[$name]);
    }
  }
}


/*
** When someone gets kicked, we need to 
** remove him/her from the members list of the chan
** If the bot get kicked, we need to remove the chan
*/

function kick_cmd($struct)
{
  $array = explode(" ", $struct->full_cmd);
  if ($struct->nick == $array[3]) {
    unset($struct->chans[$array[2]]);
  }
  else {
    unset($struct->chans[$array[2]]->members[$array[3]]);
  }
}

function debug($struct)
{
  var_dump($struct->chans);
}

function analyze_read($read, $struct)
{
  $cmd_tab = array(
		   "NOTICE" => "user_cmd",
		   "433" => "nickname_used",
		   "376" => "bot_ready", // end_motd
		   "422" => "nickname_used", // no_motd
		   "JOIN" => "join_cmd",
		   "353" => "add_user_list", // /names list
		   "PART" => "part_cmd", // someone leave the chan
		   "QUIT" => "quit_cmd",
		   "KICK" => "kick_cmd"
		   );
  /* If it is a standard command.. */
  if ($read[0] == ':') {
    $struct->full_cmd = $read;
    $buff = substr($read, strpos($read, " ", 1) + 1);
    $struct->cmd = substr($buff, 0, strpos($buff, " "));

    // here is where we check if any module needs to be activated before the core
    if (isset($struct->module_array[$struct->cmd])) {
      foreach ($struct->module_array[$struct->cmd] as $ar_mod) {
	if ($ar_mod["order"] == 0) {
	  $ar_mod["func"]($struct);
	}
      }
    }

    // here we launch the core
    if (isset($cmd_tab[$struct->cmd]))
      $cmd_tab[$struct->cmd]($struct);

    // and here we also check the modules to activate those who needs to run after the core
    if (isset($struct->module_array[$struct->cmd])) {
      foreach ($struct->module_array[$struct->cmd] as $ar_mod) {
	if ($ar_mod["order"] == 1)
	  $ar_mod["func"]($struct);    
      }
    }
  }
  else if (strncmp($read, "PING :", 6) == 0) {
    $pong_cmd = "PONG :" . substr($read, 6) . "\r\n";
    socket_write($struct->sock, $pong_cmd);
  }
}

function main_serv($struct)
{
  send_username($struct, false);
  while (($read = socket_read($struct->sock, 512, PHP_NORMAL_READ)) !== FALSE) {
    if (strlen($read) > 2) { // ignore the \n 
      $clean_read = substr($read, 0, -1); // and remove the \r;
      analyze_read($clean_read, $struct);
      echo $clean_read . "\n";
    }
  }
  echo 'Unable to read from the socket : ';
  echo socket_strerror(socket_last_error($sock)). "\n";
  return -1;
}


$SERV_URL = 'irc.freenode.org';
$PORT = 6667;

$struct = new Struct;
$struct->module_array = array();
$struct->chans = array();
$dir = opendir("modules");
if ($dir !== false) {
  while ($elem = readdir($dir)) {
    if (substr($elem, -4, 4) == ".php") {
      include "modules/".$elem;
    }
  }
}

$struct->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!socket_connect($struct->sock, $SERV_URL, $PORT)) {
  echo 'Unable to connect to ' . $SERV_URL . ':' . $PORT . "\n";
  return -1;
}
else
  return main_serv($struct);

?>

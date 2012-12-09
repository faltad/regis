<?php

/*
** Just a module which gives op status to a list of member
** when they join a channel, if they are entitled to get
** this status
*/



/*
** Someone joins a chan, so let's check if he needs to be op
*/

function admin_join($struct)
{
  $buff = explode(" ", $struct->full_cmd);
  $name = substr($buff[0], 1, strpos($buff[0], "!") - 1);
  $chan_ar = $struct->module_buffer[substr(__FILE__, strrpos(__FILE__, "modules"))];
  if (isset($chan_ar[$buff[2]])) {
    $members_op = $chan_ar[$buff[2]];
    if (in_array($name, $members_op)) {
      $cmd = "MODE " . $buff[2] . " +o $name\r\n";
      if (socket_write($struct->sock, $cmd) === false)
	echo " Error while sending op cmd\n";    
    }
  }
}

/*
** This function will check all the members of a channel after an
** "End of /NAMES list".
** But for the moment, I'm not sure if it would be useful
*/


function admin_names($struct)
{

}

/*
** As soon as we get the op status, we can give it to the other
** members of the channel
*/

function admin_mode($struct)
{
  $buff = explode(" ", $struct->full_cmd);
  if ($buff[3] == "+o" && $buff[4] == $struct->nick) {
    $chan_ar = $struct->module_buffer[substr(__FILE__, strrpos(__FILE__, "modules"))];
    if (isset($chan_ar[$buff[2]])) {
      $members_op = $chan_ar[$buff[2]];
      foreach ($struct->chans[$buff[2]]->members as $mem => $op) {
	if (in_array($mem, $members_op) && $op != true) {
	  $cmd = "MODE " . $buff[2] . " +o $mem\r\n";
	  if (socket_write($struct->sock, $cmd) === false)
	    echo " Error while sending op cmd\n";
	}
      }
    }
  }
}


/*
** Open the directory "modules/admin/" and open each of the file
** in order to get a list of members who could get op status
** the filename is in fact the chan name
*/

function init_module_admin()
{
  $dir = opendir("modules/admin");
  $ar_chan = array();
  if ($dir !== false) {
    while ($elem = readdir($dir)) {
      $ar_chan[] = $elem;
    }
    closedir($dir);
  }
  $ar_mem_chan = array();
  foreach ($ar_chan as $chan) {
    if ($chan != "." && $chan !== "..") {
      $content = file_get_contents("modules/admin/" . $chan);
      if ($content !== false) {
	$ar_mem_chan[$chan] = array();
	while (($pos = strpos($content, "\n")) !== false) {	  
	  $line = substr($content, 0, $pos);
	  if ($line[0] == "\r")
	    $line = substr($line, 1);
	  $ar_mem_chan[$chan][] = $line;
	  $content = substr($content, $pos + 1);
	}
      }
    }
  }
  return $ar_mem_chan;
}



$struct->module_array["JOIN"][] = array(
					"func" => "admin_join",
					"order"=> 0
					);
$struct->module_array["366"][] = array(
				       "func" => "admin_names",
				       "order" => 1
				       );
$struct->module_array["MODE"][] = array(
					"func" => "admin_mode",
					"order" => 1
					);

$struct->module_buffer[substr(__FILE__, strrpos(__FILE__, "modules"))] =
  init_module_admin();

?>
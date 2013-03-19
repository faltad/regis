<?php

function log_func($struct)
{
  $buff = explode(" ", $struct->full_cmd);
  $chan = $buff[2];
  $to_write = '';
  $file = fopen('modules/logs/' . "chan_$chan", "ab+");
  if ($struct->cmd == 'PRIVMSG') {
    $to_write .= date('<d/m/Y H:i:s> ');
    $to_write .= substr($buff[0], 1, strpos($buff[0], "!") - 1) . ': ';
    $msg = substr($struct->full_cmd, strpos(substr($struct->full_cmd, 1), ':') + 2);
    $to_write .= "$msg\n";
  }
  fwrite($file, $to_write);
  fclose($file);
}

$struct->module_array["PRIVMSG"][] = array(
					   "func" => "log_func",
					   "order"=> 1
					   );
?>
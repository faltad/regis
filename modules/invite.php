<?php

/*
** This module will allow the bot to join any chan
** where he has been invited to.
*/

function invite_join($struct)
{
  $buff = explode(" ", $struct->full_cmd);
  $chan = substr($buff[3], 1); // remove the :
  bot_ready($struct, $chan);

}

$struct->module_array["INVITE"][] = array(
					"func" => "invite_join",
					"order"=> 0
					);


?>
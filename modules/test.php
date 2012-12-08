<?php


/*
** This is a function handler, and will be call whenever
** there is a PRIVMSG command issued. Of course we can
** access all the content of the structure struct, which
** basically contains everything.
*/

function test_echopriv($struct) {
  $buff = explode(" ", $struct->full_cmd);
  $name = substr($buff[0], 1, strpos($buff[0], "!"));
  echo "There was a message from $name : привет $name !\n";
  if (isset($struct->chans[$buff[2]])) {
    echo "This message comes from the channel " . $buff[2] . "\n
Here is a list of members:\n";
    foreach ($struct->chans[$buff[2]]->members as $mem => $op) {
      echo "$mem\n";
    }
  }
}

function test_aftercore($struct)
{
  echo "this comes after the core\n";
}

// test of a module for Regis

echo "Initialization of module test\n";

/*
** Here, we initialize the module by adding an item in the
** module_array array, with the command we would like interact
** with, a function handler to call when this command will show up
** and an order: 0 to be call before the core, 1 after.
*/

$struct->module_array["PRIVMSG"][] = array(
					   "func" => "test_echopriv",
					   "order"=> 0


					   );
/*
** We can of course initialize several commands for the
** module, or several times the same command with a different
** function handler
*/

$struct->module_array["PRIVMSG"][] = array(
					   "func" => "test_aftercore",
					   "order"=> 1
					   );



?>

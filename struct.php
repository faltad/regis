<?php

/*
** These classes act as structures in C
*/

class Struct
{
  public $sock;
  public $cmd;
  public $full_cmd;
  public $chans;
  public $nick;
  public $module_array; // array("CMD" => array("func" => func_name, "order" => order));
};


class Chan
{
  public $name;
  public $is_op;
  public $members; // array(User, is_op); ex: array('regis' => false)
}

?>
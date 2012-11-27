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
};


class Chan
{
  public $name;
  public $is_op;
  public $members; // array(User, is_op); ex: array('regis', false)
}

?>
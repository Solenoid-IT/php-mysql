<?php



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Model;



// (Getting the values)
$user_model = new Model( new Connection(), 'database', 'user' );
$post_model = new Model( new Connection(), 'database', 'post' );



// (Getting the value)
$records = $user_model->link( [ 'App\\Models\\Post' ] )->where( 'score', '<', 64 )->list();



?>
<?php



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Model;



// (Getting the values)
$user_model = new Model( new Connection(), 'database', 'user' );
$post_model = new Model( new Connection(), 'database', 'post' );



// (Getting the value)
$records = $user_model
    ->where( 'id', '<=', 100 )
    ->rel( $post_model, fn( $model ) => $model->where( 'score', '<', 64 ) )
    ->link( [ [ $post_model, [ 'id', 'name' ] ] ] )
    ->list( [ 'id', 'name' ] )
;



?>
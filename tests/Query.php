<?php



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Query;



// (Creating a Connection)
$connection = new Connection();

// (Setting the property)
$connection->set_column_separator('.');



// (Creating a Query)
$query = new Query( $connection, 'test' );

// (Composing the query)
$cursor = $query
    ->from( 'user', 'T', 'db' )

    ->condition_start()
        ->where_raw('(')
        ->where_field( 'username', 'T' )->op( '=' )->value( 'frank' )
        ->or()
        ->where_field( 'email', 'T' )->op( '<>' )->value( 'johndoe@gmail.com' )
        ->where_raw(')')
        ->and()
        ->where_field( 'datetime.activation', 'T' )->op( 'IS NOT' )->value( null )
    ->condition_end()

    ->select_field( 'id', 'T' )
    ->select_field( 'datetime.activation', 'T' )

    ->order_by( 'id', 'DESC', 'T' )

    ->run()
;



// (Getting the value)
$records = $cursor->list();



// debug
print_r($records);



?>
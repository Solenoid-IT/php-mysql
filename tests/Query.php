<?php



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Condition;
use \Solenoid\MySQL\Query;



// (Creating a Connection)
$connection = new Connection();

// (Setting the property)
$connection->set_column_separator('.');



// (Creating a Query)
$query = new Query( $connection, 'test' );

// (Composing the query)
$cursor = $query
    ->from( 'db', 'user', 'T' )

    ->condition
    (
        ( new Condition( $connection ) )
            ->where_raw('(')
            ->where_column( 'T', 'username' )->op('=')->value('frank')
            ->or()
            ->where_column( 'T', 'email' )->op('<>')->value('johndoe@gmail.com')
            ->where_raw(')')
            ->and()
            ->where_column( 'T', 'datetime.activation' )->op('IS NOT')->value(null)
    )

    ->select( 'T', 'id' )
    ->select( 'T', 'datetime.activation' )

    ->order_by( 'T', 'id', 'DESC' )

    ->run()
;



// (Getting the value)
$records = $cursor->to_array();



// debug
print_r($records);



?>
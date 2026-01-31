<?php



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Model;



// (Getting the value)
$model = new Model( new Connection(), 'database', 'table' );



// (Getting the value)
$cursor = $model->where( 'score', '>', 64 )->cursor( [ 'id', 'name', 'surname' ] );

while ( $record = $cursor->read() )
{// Processing each entry
    // Printing the value
    echo json_encode( $record ) . "\n";
}



// (Closing the cursor)
$cursor->close();



?>
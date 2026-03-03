<?php



include_once ( __DIR__ . '/../vendor/autoload.php' );



use Solenoid\MySQL\Connection;
use Solenoid\MySQL\Command;
use Solenoid\MySQL\Model;



$connection = new Connection
(
    '127.0.0.1', 
    3306, 
    'user', 
    'pass'
)
;



$command = new Command( 'CREATE DATABASE IF NOT EXISTS `db`;' );

$connection->execute( $command );



$command = new Command
(
    <<<EOD
    CREATE TABLE IF NOT EXISTS `db`.`user`
    (
        `id`        BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,

        `hierarchy` INT UNSIGNED                   NOT NULL,

        `name`      VARCHAR(255)                   NOT NULL,

        PRIMARY KEY (`id`),

        UNIQUE KEY (`name`)
    )
    ;
    EOD
)
;

$connection->execute( $command );



$model = new Model( $connection, 'db', 'user' );
$model->empty();



$model->insert
(
    [

        [
            'hierarchy' => 1,
            'name'      => 'User 1'
        ],
        [
            'hierarchy' => 2,
            'name'      => 'User 2'
        ],
        [
            'hierarchy' => 3,
            'name'      => 'User 3'
        ]
    ],

    true
)
;

echo "Inserted ID: {$model->last_id()}\n\n";



$model->where( 'id', 3 )->update( [ 'name' => 'User 3 (updated)' ] );



/*

$command = new Command
(
    "SELECT * FROM `db`.`user` WHERE `hierarchy` > :hierarchy",
    [ 'hierarchy' => 1 ]
)
;

echo "Command: {$command->simulate()}\n\n";

$connection->execute( $command );

print_r( $connection->cursor()->list() );

*/



$model->reset();


/*

$records = $model->where( 'hierarchy', '>', 1 )->list();

print_r( $records );

*/



$cursor = $model->where( 'hierarchy', '>', 1 )->cursor();

while ( $record = $cursor->read() )
{
    print_r( $record );
}



?>
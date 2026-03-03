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



$command = new Command( 'DROP DATABASE IF EXISTS `db`;' );

$connection->execute( $command );



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

        `phone`     VARCHAR(255)                       NULL,

        `enabled`   BOOLEAN                        NOT NULL,

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
            'name'      => 'User 1',
            'phone'     => '1234567890',
            'enabled'   => true
        ],
        [
            'hierarchy' => 2,
            'name'      => 'User 2',
            'phone'     => '0987654321',
            'enabled'   => false
        ],
        [
            'hierarchy' => 3,
            'name'      => 'User 3',
            'phone'     => null,
            'enabled'   => true
        ]
    ],

    true
)
;

echo "Inserted ID: {$model->last_id()}\n\n";



#print_r( $model->where( 'enabled', true )->list() );exit;
#print_r( $model->where( 'phone', 'IS NOT', null )->list() );exit;



$model->where( 'id', 3 )->update( [ 'name' => 'User 3 (updated)' ] );



$model->reset();



$model->where( 'id', 2 )->delete();



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



/*

$record = $model->where( 'hierarchy', '>', 1 )->find();

print_r( $record );

*/



$cursor = $model->where( 'hierarchy', '>', 1 )->cursor();

while ( $record = $cursor->read() )
{
    print_r( $record );
}



?>
<?php



include_once ( __DIR__ . '/../vendor/autoload.php' );



use Solenoid\MySQL\Connection;



$connection = new Connection
(
    '127.0.0.1', 
    3306, 
    'user', 
    'pass'
)
;



$command = 'CREATE DATABASE IF NOT EXISTS `db`;';

$connection->execute( $command );



$command =
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
;

$connection->execute( $command );



$command = 
    <<<EOD
    INSERT IGNORE INTO `db`.`user` (`hierarchy`, `name`) VALUES
    (1, 'User 1'),
    (2, 'User 2'),
    (3, 'User 3')
    ;
    EOD
;

$connection->execute( $command );



$command = "SELECT * FROM `db`.`user` WHERE `hierarchy` > :hierarchy";
$values  = [ 'hierarchy' => 1 ];

$connection->execute( $command, $values );

echo "Command: {$connection->simulated_command}\n\n";
print_r( $connection->cursor()->list() );



?>
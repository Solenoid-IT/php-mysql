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



$command =
    <<<EOD
    CREATE TABLE IF NOT EXIST `user`
    (
        `id`        BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,

        `hierarchy` INT UNSIGNED                   NOT NULL,

        `name`      VARCHAR(255)                   NOT NULL,

        PRIMARY KEY (`id`)
    )
    ;
    EOD
;

$connection->execute( $command );



$command = 
    <<<EOD
    INSERT INTO `user` (`hierarchy`, `name`) VALUES
    (1, 'User 1'),
    (2, 'User 2'),
    (3, 'User 3')
    ;
    EOD
;

$connection->execute( $command );



$command = "SELECT * FROM `user` WHERE `hierarchy` > :hierarchy";
$values  = [ 'hierarchy' => 1 ];

$connection->execute( $command, $values );



?>
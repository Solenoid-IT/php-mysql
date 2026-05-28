<?php



include_once ( __DIR__ . '/../../vendor/autoload.php' );
include_once ( __DIR__ . '/helpers.php' );

include_once ( __DIR__ . '/Models/User.php' );
include_once ( __DIR__ . '/Models/Hierarchy.php' );




use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\ConnectionMap;
use \Solenoid\MySQL\Model;
use \Solenoid\MySQL\Command;
use \Solenoid\MySQL\Condition;

use \App\Models\User as UserModel;
use \App\Models\Hierarchy as HierarchyModel;



function create_database () : Connection
{
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



    $command = new Command( 'CREATE DATABASE `db`;' );

    $connection->execute( $command );



    return $connection;
}

function set_connection_map (Connection $connection) : void
{
    $connection_map = new ConnectionMap();
    $connection_map->set( 'local', $connection );

    Model::set_map( $connection_map );
}

function create_hierarchy_table (Connection $connection) : HierarchyModel
{
    $command = new Command
    (
        <<<EOD
        CREATE TABLE IF NOT EXISTS `db`.`hierarchy`
        (
            `id`        INT UNSIGNED AUTO_INCREMENT NOT NULL,

            `name`      VARCHAR(255)                   NOT NULL,

            PRIMARY KEY (`id`),

            UNIQUE KEY (`name`)
        )
        ;
        EOD
    )
    ;

    $connection->execute( $command );



    $hierarchy_model = model( HierarchyModel::class );
    $hierarchy_model->empty();

    $hierarchy_model->insert
    (
        [

            [
                'name' => 'admin'
            ],
            [
                'name' => 'user'
            ],
            [
                'name' => 'guest'
            ]
        ],

        true
    )
    ;



    return $hierarchy_model;
}

function create_user_table (Connection $connection) : UserModel
{
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



    $user_model = model( UserModel::class );
    $user_model->empty();

    $user_model->insert
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



    return $user_model;
}



$connection = create_database();
set_connection_map( $connection );

$hierarchy_model = create_hierarchy_table( $connection );
$user_model      = create_user_table( $connection );



$records = $user_model
    ->where( 'id', '<=', 100 )
    ->and()
    ->rel( $hierarchy_model, fn (Condition $c) => $c->where( 'name', 'guest' ) )
    ->link( [ [ $hierarchy_model, [ 'id', 'name' ] ] ] )
    ->list( [ 'id', 'name' ] )
;

print_r( $records );
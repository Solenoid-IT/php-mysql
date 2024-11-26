<?php



namespace Solenoid\MySQL;



class ConnectionStore
{
    public static array $connections = [];



    # Returns [self]
    public static function set (string $id, Connection &$connection)
    {
        // (Getting the value)
        self::$connections[ $id ] = &$connection;
    }
}



?>
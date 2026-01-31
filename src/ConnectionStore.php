<?php



namespace Solenoid\MySQL;



class ConnectionStore
{
    public static array $connections = [];



    # Returns [Connection|false]
    public static function get (string $id)
    {
        // Returning the value
        return self::$connections[ $id ] ?? false;
    }

    # Returns [void]
    public static function set (string $id, Connection &$connection)
    {
        // (Getting the value)
        self::$connections[ $id ] = &$connection;
    }
}



?>
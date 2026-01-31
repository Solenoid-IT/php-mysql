<?php



namespace Solenoid\MySQL\DDL;



class Reference
{
    public ?string $database;
    public  string $table;
    public  array  $key;



    # Returns [self]
    public function __construct (?string $database = null, string $table, array $key)
    {
        // (Getting the values)
        $this->database = $database;
        $this->table    = $table;
        $this->key      = $key;
    }

    # Returns [Reference]
    public static function create (?string $database = null, string $table, array $key)
    {
        // Returning the value
        return new Reference( $database, $table, $key );
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return 'REFERENCES ' . implode( '.', array_map( function ($k) { return "`$k`"; }, array_filter( [ $this->database, $this->table ], function ($k) { return $k !== null; } ) ) ) . ' (' . implode( ',', array_map( function ($k) { return "`$k`"; }, $this->key ) ) . ')';
    }
}



?>
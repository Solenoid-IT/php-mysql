<?php



namespace Solenoid\MySQL\DDL;



class Entity
{
    public ?string $database;
    public  string $name;

    public  array  $fields;

    public  array  $primary_key;

    public  array  $unique_keys;

    public  array  $foreign_keys;



    # Returns [self]
    public function __construct (?string $database = null, string $name, array $fields, array $primary_key = [], array $unique_keys = [], array $foreign_keys = [])
    {
        // (Getting the values)
        $this->database     = $database;
        $this->$name        = $name;

        $this->fields       = $fields;

        $this->primary_key  = $primary_key;

        $this->unique_keys  = $unique_keys;

        $this->foreign_keys = $foreign_keys;
    }

    # Returns [Entity]
    public static function create (?string $database = null, string $name, array $fields, array $primary_key = [], array $unique_keys = [], array $foreign_keys = [])
    {
        // Returning the value
        return new Entity( $database, $name, $fields, $primary_key, $unique_keys, $foreign_keys );
    }



    # Returns [string]
    public function __toString ()
    {
        // (Getting the value)
        $table_id = implode( '.', array_map( function ($k) { return "`$k`"; }, array_filter( [ $this->database, $this->name ], function ($k) { return $k !== null; } ) ));



        // (Setting the value)
        $components = [];



        // (Appending the value)
        $components[] = array_map( function ($k, $v) { return "`$k`\t$v"; }, $this->fields );



        // (Appending the value)
        $components[] = 'PRIMARY KEY (' . implode( ',', array_map( function ($k) { return "`$k`"; }, $this->primary_key ) ) . ')';



        foreach ($this->unique_keys as $unique_key)
        {// Processing each entry
            // (Appending the value)
            $components[] = 'UNIQUE  KEY (' . implode( ',', array_map( function ($k) { return "`$k`"; }, $unique_key ) ) . ')';
        }



        foreach ($this->foreign_keys as $foreign_key)
        {// Processing each entry
            // (Appending the value)
            $components[] = $foreign_key;
        }



        // (Getting the value)
        $components = implode( ",\n", $components );



        // Returning the value
        return
            <<<EOD
            CREATE TABLE $table_id
            (
               $components
            )
            ;
            EOD
        ;
    }
}



?>
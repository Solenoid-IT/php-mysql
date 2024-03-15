<?php



namespace Solenoid\MySQL\DDL;



class Database
{
    const CHARSET = 'utf8mb4';
    const COLLATE = 'utf8mb4_unicode_ci';



    public string $name;

    public string $charset;
    public string $collate;

    public array  $entities;



    # Returns [self]
    public function __construct (string $name, string $charset = self::CHARSET, string $collate = self::COLLATE)
    {
        // (Getting the values)
        $this->$name   = $name;

        $this->charset = $charset;
        $this->collate = $collate;
    }

    # Returns [Database]
    public static function create (string $name, string $charset = self::CHARSET, string $collate = self::COLLATE)
    {
        // Returning the value
        return new Database( $name, $charset, $collate );
    }



    # Returns [self]
    public function set_entities (array $entities)
    {
        // (Getting the value)
        $this->entities = $entities;



        // Returning the value
        return $this;
    }



    # Returns [string]
    public function build ()
    {
        // Returning the value
        return
            $this . ( $this->entities ? "\n\n\n" . implode( "\n", $this->entities ) : '' )
        ;
    }



    # Returns [Database|false]
    public static function parse (string $sql)
    {
        if ( preg_match( '/CREATE\s+DATABASE\s+\`([^\`]+)\`(\s+CHARACTER\s+SET\s+([^\s]+)(\s+COLLATE\s+([^\s]+))?)?\s+\;/', $sql, $matches ) !== 1 )
        {// Match failed
            // Returning the value
            return false;
        }



        // (Creating a Database)
        $database = Database::create( $matches[1], $matches[3], $matches[5] );



        if ( preg_match_all( '/CREATE\s+TABLE\s+\`([^\s]+)\`/', $sql, $matches, PREG_OFFSET_CAPTURE ) === 1 )
        {// Match OK
            // (Getting the value)
            $table = $matches[1][0];

            // (Getting the value)
            $start_pos = $matches[0][1];
        }



        // Returning the value
        return $database;
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return
            <<<EOD
            CREATE DATABASE `$this->name`
            CHARACTER SET $this->charset
            COLLATE $this->collate
            ;
            EOD
        ;
    }
}



?>
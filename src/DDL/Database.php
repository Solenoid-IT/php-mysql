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
        $this->name    = $name;

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
        if ( preg_match( '/CREATE\s+DATABASE\s+\`([^\`]+)\`(\s+CHARACTER\s+SET\s+([^\s]+)(\s+COLLATE\s+([^\s]+))?)?\s+\;/', $sql, $database_matches ) !== 1 )
        {// Match failed
            // Returning the value
            return false;
        }



        // (Creating a Database)
        $database = Database::create( $database_matches[1], $database_matches[3], $database_matches[5] );



        // (Setting the value)
        $entities = [];

        if ( preg_match_all( '/CREATE\s+TABLE\s+\`([^\`]+)\`/', $sql, $table_matches, PREG_OFFSET_CAPTURE ) > 0 )
        {// Match OK
            foreach ($table_matches[1] as $v)
            {// Processing each entry
                // (Getting the value)
                $table = $v[0];

                // (Getting the value)
                $start_pos = $v[1] + strlen( $v[0] );



                // (Getting the value)
                $part = substr( $sql, $start_pos );

                if ( preg_match( '/\)\s*\;/', $part, $end_table_matches, PREG_OFFSET_CAPTURE ) === 1 )
                {// Match OK
                    // (Getting the value)
                    $end_pos = $end_table_matches[0][1];



                    // (Getting the value)
                    $part = substr( $part, 0, $end_pos );

                    if ( preg_match_all( '/\`([^\`]+)\`\s+([^\,]+)\,?\s+/', $part, $field_matches ) > 0 )
                    {// Match OK
                        // (Getting the value)
                        $fields = [];

                        foreach ($field_matches[0] as $k => $v)
                        {// Processing each entry
                            // (Getting the value)
                            $fields[ $field_matches[1][$k] ] = $field_matches[2][$k];
                        }



                        // (Setting the value)
                        $primary_key = [];

                        if ( preg_match( '/PRIMARY\s+KEY\s+\(([^\)]+)\)/', $part, $primary_key_matches ) === 1 )
                        {// Match OK
                            // (Getting the value)
                            $primary_key = array_map( function ($v) { return preg_replace( '/\`([^\`]+)\`/', '$1', $v ); }, explode( ',', $primary_key_matches[1] ) );
                        }



                        // (Setting the value)
                        $unique_keys = [];

                        if ( preg_match_all( '/UNIQUE\s+KEY\s+\(([^\)]+)\)/', $part, $unique_key_matches ) > 0 )
                        {// Match OK
                            foreach ($unique_key_matches[1] as $v)
                            {// Processing each entry
                                // (Appending the value)
                                $unique_keys[] = array_map( function ($v) { return preg_replace( '/\`([^\`]+)\`/', '$1', $v ); }, explode( ',', $v ) );
                            }
                        }


                        // (Setting the value)
                        $foreign_keys = [];

                        if ( preg_match_all( '/FOREIGN\s+KEY\s+\(([^\)]+)\)\s+REFERENCES\s+ON\s+\`([^\`]+)\`(\.\`([^\`]+)\`)?\s+\(([^\)]+)\)(\s+(ON\s+UPDATE\s+([^\s]+)))?(\s+(ON\s+DELETE\s+([^\s]+)))?/', $part, $foreign_key_matches ) > 0 )
                        {// Match OK
                            foreach ($foreign_key_matches[0] as $k => $v)
                            {// Processing each entry
                                // (Getting the values)
                                $key          = array_map( function ($v) { return preg_replace( '/\`([^\`]+)\`/', '$1', $v ); }, explode( ',', $foreign_key_matches[1][$k] ) );

                                $ref_database = $foreign_key_matches[2][$k] && $foreign_key_matches[4][$k] ? $foreign_key_matches[2][$k] : null;
                                $ref_table    = $foreign_key_matches[2][$k] && $foreign_key_matches[4][$k] ? $foreign_key_matches[4][$k] : $foreign_key_matches[2][$k];
                                $ref_key      = array_map( function ($v) { return preg_replace( '/\`([^\`]+)\`/', '$1', $v ); }, explode( ',', $foreign_key_matches[5][$k] ) );



                                // (Setting the value)
                                $ref_rules = [];

                                if ( $foreign_key_matches[7][$k] )
                                {// Value found
                                    // (Appending the value)
                                    $ref_rules[] = $foreign_key_matches[7][$k];
                                }

                                if ( $foreign_key_matches[10][$k] )
                                {// Value found
                                    // (Appending the value)
                                    $ref_rules[] = $foreign_key_matches[10][$k];
                                }



                                // (Appending the value)
                                $foreign_keys[] = 'ahcid';
                            }
                        }



                        // (Appending the value)
                        $entities[] = Entity::create
                        (
                            $database->name,
                            $table,
                            $fields,
                            $primary_key,
                            $unique_keys,
                            $foreign_keys
                        )
                        ;
                    }
                }
            }
        }



        // (Setting the entities)
        $database->set_entities( $entities );



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
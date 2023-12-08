<?php



namespace Solenoid\MySQL;



class Table
{
    private Connection $connection;

    public string      $database;
    public string      $table;



    # Returns [self]
    public function __construct (Connection &$connection, string $database, string $table)
    {
        // (Getting the values)
        $this->connection = $connection;

        $this->database   = str_replace( '`', '', $database );
        $this->table      = str_replace( '`', '', $table );
    }

    # Returns [Table]
    public static function select (Connection &$connection, string $database, string $table)
    {
        // Returning the value
        return new Table( $connection, $database, $table );
    }



    # Returns [self|false] | Throws [Exception]
    public function rename (string $name)
    {
        // (Getting the value)
        $name = str_replace( '`', '', $name );

        if ( !$this->connection->execute( "RENAME TABLE `$this->database`.`$this->table` TO `$name`;" ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false] | Throws [Exception]
    public function copy (string $database, string $table, bool $copy_data = false)
    {
        // (Getting the values)
        $database = str_replace( '`', '', $database );
        $table    = str_replace( '`', '', $table );

        if ( !$this->connection->execute( "CREATE TABLE `$database`.`$table` LIKE `$this->database`.`$this->table`;" ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }

        if ( $copy_data )
        {// Value is true
            // (Getting the query)
            $query =
            "
                INSERT
                    INTO `$database`.`$table`
                SELECT
                    *
                FROM
                    `$this->database`.`$this->table`
                ;
            "
            ;

            if ( !$this->connection->execute( $query ) )
            {// (Unable to execute the query)
                // (Setting the value)
                $message = "Unable to execute the query :: " . $this->connection->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false] | Throws [Exception]
    public function transfer (string $database, string $table)
    {
        // (Getting the values)
        $database = str_replace( '`', '', $database );
        $table    = str_replace( '`', '', $table );



        // (Getting the query)
        $query =
        "
            INSERT
                INTO `$database`.`$table`
            SELECT
                *
            FROM
                `$this->database`.`$this->table`
            ;
        "
        ;

        if ( !$this->connection->execute( $query ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false] | Throws [Exception]
    public function delete ()
    {
        if ( !$this->connection->execute( "DROP TABLE `$this->database`.`$this->table`;" ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [array<assoc>|false] | Throws [Exception]
    public function list_columns ()
    {
        // (Setting the query)
        $query =
        "
            SHOW
                COLUMNS
            FROM
                `$this->database`.`$this->table`
            ;
        "
        ;

        if ( !$this->connection->execute( $query ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this->connection->fetch_cursor()->to_array();
    }

    # Returns [array<string>|false] | Throws [Exception]
    public function list_column_values (string $column)
    {
        // (Getting the value)
        $column = str_replace( '`', '', $column );



        // (Getting the query)
        $query =
        "
            SELECT DISTINCT
                `$column` AS `value`
            FROM
                `$this->database`.`$this->table`
            ;
        "
        ;

        if ( !$this->connection->execute( $query ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this->connection->fetch_cursor()->set_mode('value')->to_array();
    }
}



?>
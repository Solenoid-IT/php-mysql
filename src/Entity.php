<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\QueryRunner;



class Entity
{
    private int       $lid;



    public Connection $connection;
    public string     $database;
    public string     $table;



    # Returns [self]
    public function __construct (Connection &$connection, string $database, string $table)
    {
        // (Getting the values)
        $this->connection = &$connection;
        $this->database   = $database;
        $this->table      = $table;
    }



    # Returns [assoc|false]
    public function list_columns ()
    {
        if ( !$this->connection->execute( "SHOW FIELDS FROM `$this->database`.`$this->table`" ) )
        {// (Unable to get the fields metadata)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this->connection->fetch_cursor()->list();
    }



    # Returns [self|false]
    public function register (array $records, bool $ignore_error = false)
    {
        // (Getting the value)
        $this->lid = (int) $this->connection->get_last_insert_id();



        // (Getting the value)
        $columns = implode( ',', array_map( function ($column) { $column = str_replace( '`', '', $column ); return "`$column`"; }, array_keys( $records[0] ) ) );



        // (Setting the value)
        $values = [];

        foreach ( $records as $record )
        {// Processing each entry
            // (Appending the value)
            $values[] = '(' . implode( ',', array_map( function ($v) { return $this->connection->normalize_value( $v ); }, array_values( $record ) ) ) . ')';
        }



        // (Getting the value)
        $values = implode( ",\n", $values );



        // (Getting the value)
        $ignore = $ignore_error ? ' IGNORE' : '';



        // (Getting the value)
        $query =
            <<<EOD
            INSERT$ignore INTO `$this->database`.`$this->table` ($columns)
            VALUES
                $values
            ;
            EOD
        ;

        if ( !$this->connection->execute( $query ) )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false]
    public function unregister (array $filters = [])
    {
        if ( !QueryRunner::create( $this->connection, $this->database, $this->table )->filter( $filters )->delete() )
        {// (Unable to delete the records)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [self|false] | Throws [Exception]
    public function change (array $filters, array $kv_data)
    {
        if ( !QueryRunner::create( $this->connection, $this->database, $this->table )->filter( $filters )->update( $kv_data ) )
        {// (Unable to update the records)
            // (Setting the value)
            $message = "Unable to update the records :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [assoc|false] | Throws [Exception]
    public function find (array $filters)
    {
        // (Getting the value)
        $record = QueryRunner::create( $this->connection, $this->database, $this->table )->set_auto_type()->set_column_separator()->filter( $filters )->select()->fetch_head();

        if ( $record === false )
        {// (Record not found)
            // Returning the value
            return false;
        }



        // Returning the value
        return $record;
    }

    # Returns [array<assoc>] | Throws [Exception]
    public function list (array $filters = [], array $order = [], ?callable $transform_entry = null)
    {
        // (Getting the value)
        $qr = QueryRunner::create( $this->connection, $this->database, $this->table )->set_auto_type()->set_column_separator()->filter( $filters );

        if ( $order )
        {// Value found
            // (Composing the query runner)
            $qr->order_by( $order );
        }



        // Returning the value
        return $qr->select()->list( $transform_entry );
    }



    # Returns [Cursor|false] | Throws [Exception]
    public function set (array $kv_data, array $key, bool $ignore_error = false)
    {
        // Returning the value
        return QueryRunner::create( $this->connection, $this->database, $this->table )->set( $kv_data, [], $key, $ignore_error );
    }



    # Returns [array<int>] | Throws [Exception]
    public function fetch_ids ()
    {
        // (Getting the values)
        $last  = (int) $this->connection->get_last_insert_id();
        $first = $last - $this->lid;



        // (Setting the value)
        $ids = [];

        for ($i = $first; $i <= $last; $i++)
        {// Iterating each index
            // (Appending the value)
            $ids[] = $i;
        }



        // Returning the value
        return $ids;
    }



    # Returns [self|false]
    public function empty ()
    {
        // (Getting the value)
        $query =
            <<<EOD
            TRUNCATE TABLE `$this->database`.`$this->table`;
            EOD
        ;

        if ( !$this->connection->execute( $query ) )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [self|false]
    public function copy (string $dst_database, string $dst_table)
    {
        // (Getting the value)
        $query =
            <<<EOD
            CREATE TABLE `$dst_database`.`$dst_table` LIKE `$this->database`.`$this->table`;
            EOD
        ;

        if ( !$this->connection->execute( $query ) )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false]
    public function remove ()
    {
        // (Getting the value)
        $query =
            <<<EOD
            DROP TABLE IF EXISTS `$this->database`.`$this->table`;
            EOD
        ;

        if ( !$this->connection->execute( $query ) )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [self|false]
    public function run (string $query, array $kv_data = [])
    {
        if ( !$this->connection->execute( $query, $kv_data ) )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [Cursor]
    public function fetch_cursor ()
    {
        // Returning the value
        return $this->connection->fetch_cursor();
    }



    # Returns [QueryRunner]
    public function compose ()
    {
        // Returning the value
        return QueryRunner::create( $this->connection, $this->database, $this->table );
    }



    # Returns [string|null]
    public function get_error_text ()
    {
        // Returning the value
        return $this->connection->get_error_text();
    }
}



?>
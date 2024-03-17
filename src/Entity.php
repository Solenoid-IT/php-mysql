<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;



class Entity
{
    public Connection $connection;
    public string     $database;
    public string     $table;



    # Returns [Entity]
    public function __construct (Connection &$connection, string $database, string $table)
    {
        // (Getting the values)
        $this->connection = &$connection;
        $this->database   = $database;
        $this->table      = $table;
    }

    # Returns [self]
    public static function create (Connection &$connection, string $database, string $table)
    {
        // Returning the value
        return new Entity( $connection, $database, $table );
    }



    # Returns [bool] | Throws [Exception]
    public function register (array $kv_data, string &$id = null)
    {
        if ( !QueryRunner::create( $this->connection, $this->database, $this->table )->insert( $kv_data ) )
        {// (Unable to insert the record)
            // (Setting the value)
            $message = "Unable to insert the record :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( !$this->connection->execute( "SHOW FIELDS FROM `$this->database`.`$this->table`" ) )
        {// (Unable to get the fields metadata)
            // (Setting the value)
            $message = "Unable to get the fields metadata :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // (Getting the value)
        $ai_fields = array_values( array_filter( $this->connection->fetch_cursor()->to_array(), function($field) { return stripos( $field['Extra'], 'auto_increment' ) !== false; },  ) );

        if ( $ai_fields )
        {// Value is not empty
            // (Getting the value)
            $id = $this->connection->get_last_insert_id();
        }



        // Returning the value
        return true;
    }

    # Returns [bool] | Throws [Exception]
    public function unregister (array $filters = [])
    {
        if ( !QueryRunner::create( $this->connection, $this->database, $this->table )->filter( $filters )->delete() )
        {// (Unable to delete the records)
            // (Setting the value)
            $message = "Unable to delete the records :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return true;
    }



    # Returns [assoc|false] | Throws [Exception]
    public function change (array $filters, array $kv_data)
    {
        if ( QueryRunner::create( $this->connection, $this->database, $this->table )->filter( $filters )->update( $kv_data ) )
        {// (Unable to update the records)
            // (Setting the value)
            $message = "Unable to update the records :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return true;
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
    public function list (array $filters = [])
    {
        // Returning the value
        return QueryRunner::create( $this->connection, $this->database, $this->table )->set_auto_type()->set_column_separator()->filter( $filters )->order_by( [ 'id' => 'desc' ] )->select()->to_array();
    }
}



?>
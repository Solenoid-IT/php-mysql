<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Condition;



class Model
{
    private int $lid;



    public Connection $connection;
    public string     $database;
    public string     $table;

    public ?Condition $condition;

    public array      $group_columns;
    public array      $order_columns;

    public int        $limit;
    public ?int       $offset;



    # Returns [self]
    public function __construct (Connection &$connection, string $database, string $table)
    {
        // (Getting the values)
        $this->connection    = &$connection;
        $this->database      = str_replace( '`', '', $database );
        $this->table         = str_replace( '`', '', $table );

        $this->condition     = null;
        
        $this->group_columns = [];
        $this->order_columns = [];
    }



    # Returns [self|false]
    public function insert (array $records, bool $ignore_error = false)
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
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [Cursor|false]
    public function select (Query $query)
    {
        // Returning the value
        return $query->from( $this->database, $this->table, 'T', true )->run();
    }

    # Returns [self|false]
    public function update (array $values)
    {
        // (Getting the value)
        $condition = $this->condition ?? ( new Condition() )->set_connection( $this->connection );



        // (Setting the value)
        $kv_values = [];

        foreach ( $values as $k => $v )
        {// Processing each entry
            // (Appending the value)
            $kv_values[] = '`' . str_replace( '`', '', $k ) . '`' . ' = ' . $this->connection->normalize_value( $v );
        }



        // (Getting the value)
        $kv_values = implode( ",\n\t", $kv_values );



        // (Getting the value)
        $cmd =
            <<<EOD
            UPDATE `$this->database`.`$this->table`
            SET
                $kv_values
            WHERE
                $condition
            ;
            EOD
        ;

        if ( !$this->connection->execute( $cmd ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false]
    public function delete ()
    {
        // (Getting the value)
        $condition = $this->condition ?? ( new Condition() )->set_connection( $this->connection );



        if ( !$this->connection->execute( "DELETE\nFROM\n\t`$this->database`.`$this->table`\nWHERE\n\t$condition\n;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [Cursor|false] | Throws [Exception]
    public function set (array $values = [], array $key = [], bool $ignore_error = false)
    {
        // (Getting the value)
        $key_values =
        [
            'normalized' => [],
            'raw'        => []
        ]
        ;

        foreach ($key as $key_component)
        {// Processing each entry
            if ( $values[ $key_component ] )
            {// Value found
                // (Getting the value)
                $key_values['normalized'][ $key_component ] = $values[ $key_component ];
            }
        }



        // (Getting the value)
        $where_kv_data = $key_values['normalized'];



        // (Getting the value)
        $cursor = ( new Query( $this->connection ) )
            ->from( $this->database, $this->table )

            ->condition_start()
                ->filter( [ $where_kv_data ] )
            ->condition_end()

            ->select_all()

            ->run()
        ;

        if ( $cursor->is_empty() )
        {// (Record not found)
            if ( $this->insert( [ $values ], $ignore_error ) === false )
            {// (Unable to insert the record)
                // (Setting the value)
                $message = "Unable to insert the record :: " . $this->connection->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }
        else
        {// (Record found)
            // (Getting the value)
            $n_kv_data = array_diff_assoc( $values, $key_values['normalized'] );

            if ( $this->filter( [ $key_values['normalized'] ] )->update( $n_kv_data ) === false )
            {// (Unable to update the record)
                // (Setting the value)
                $message = "Unable to update the record :: " . $this->connection->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }



        // (Getting the value)
        $cursor = ( new Query( $this->connection ) )
            ->from( $this->database, $this->table )

            ->condition_start()
                ->filter( [ $where_kv_data ] )
            ->condition_end()

            ->select_all()

            ->run()
        ;



        // (Setting the value)
        $this->condition = null;



        // Returning the value
        return $cursor;
    }



    # Returns [Query]
    public function query ()
    {
        // (Creating a Query)
        $query = new Query( $this->connection );

        // (Composing the query)
        $query->from( $this->database, $this->table, 'T', true );



        // Returning the value
        return $query;
    }



    # Returns [self]
    public function filter (array $filter = [])
    {
        // (Getting the value)
        $this->condition = ( new Condition() )->set_connection( $this->connection )->set_model( $this );



        // (Composing the condition)
        $this->condition->filter($filter);



        // Returning the value
        return $this;
    }

    # Returns [Condition]
    public function condition_start ()
    {
        // Returning the value
        return $this->condition = ( new Condition() )->set_connection( $this->connection )->set_model( $this );
    }



    # Returns [int]
    public function count (?string $field = null)
    {
        // (Getting the value)
        $query = $this->query()->condition( $this->condition );



        if ( $field )
        {// Value found
            // (Composing the query)
            $query->count_field( null, $field );
        }
        else
        {// Value not found
            // (Composing the query)
            $query->count_all( null, 'num_records' );
        }



        // Returning the value
        return (int) $query->run()->set_mode( 'value' )->fetch_head();
    }

    # Returns [Record|false]
    public function find (array $fields = [], bool $exclude_fields = false, bool $typed_fields = true, ?callable $transform_record = null)
    {
        // (Getting the value)
        $query = $this->query()->condition( $this->condition );



        // (Getting the value)
        $fields = array_unique( $fields );

        if ( $fields )
        {// Value is not empty
            if ( $exclude_fields )
            {// (Fields are excluded)
                // (Getting the value)
                $table_fields = array_keys( $this->describe() );



                // (Getting the value)
                $include_fields = array_diff( $table_fields, $fields );

                foreach ( $include_fields as $field )
                {// Processing each entry
                    // (Composing the query)
                    $query->select_field( null, $field );
                }
            }
            else
            {// (Fields are included)
                foreach ( $fields as $field )
                {// Processing each entry
                    // (Composing the query)
                    $query->select_field( null, $field );
                }
            }
        }
        else
        {// Value is empty
            // (Composing the query)
            $query->select_all();
        }



        foreach ( $this->group_columns as $column )
        {// Processing each entry
            // (Composing the query)
            $query->group_by( null, $column );
        }



        foreach ( $this->order_columns as $column => $direction )
        {// Processing each entry
            // (Composing the query)
            $query->order_by( null, $column, $direction );
        }



        if ( isset( $this->limit ) )
        {// Value is set
            // (Composing the query)
            $query->limit( $this->limit );
        }

        if ( isset( $this->offset ) )
        {// Value is set
            // (Composing the query)
            $query->offset( $this->offset );
        }



        // Returning the value
        return $query->run()->set_typed_fields( $typed_fields )->fetch_head( $transform_record );
    }

    # Returns [array<Record>]
    public function list (array $fields = [], bool $exclude_fields = false, bool $typed_fields = true, ?callable $transform_record = null)
    {
        // (Getting the value)
        $condition = $this->condition ?? ( new Condition() )->set_connection( $this->connection );



        // (Getting the value)
        $query = $this->query()->condition( $condition );

        

        // (Getting the value)
        $fields = array_unique( $fields );

        if ( $fields )
        {// Value is not empty
            if ( $exclude_fields )
            {// (Fields are excluded)
                // (Getting the value)
                $table_fields = array_keys( $this->describe() );



                // (Getting the value)
                $include_fields = array_diff( $table_fields, $fields );

                foreach ( $include_fields as $field )
                {// Processing each entry
                    // (Composing the query)
                    $query->select_field( null, $field );
                }
            }
            else
            {// (Fields are included)
                foreach ( $fields as $field )
                {// Processing each entry
                    // (Composing the query)
                    $query->select_field( null, $field );
                }
            }
        }
        else
        {// Value is empty
            // (Composing the query)
            $query->select_all();
        }



        foreach ( $this->group_columns as $column )
        {// Processing each entry
            // (Composing the query)
            $query->group_by( null, $column );
        }



        foreach ( $this->order_columns as $column => $direction )
        {// Processing each entry
            // (Composing the query)
            $query->order_by( null, $column, $direction );
        }



        if ( isset( $this->limit ) )
        {// Value is set
            // (Composing the query)
            $query->limit( $this->limit );
        }

        if ( isset( $this->offset ) )
        {// Value is set
            // (Composing the query)
            $query->offset( $this->offset );
        }



        // Returning the value
        return $query->run()->set_typed_fields( $typed_fields )->list( $transform_record );
    }



    # Returns [array<int>] | Throws [Exception]
    public function fetch_ids ()
    {
        // (Getting the values)
        $last  = (int) $this->connection->get_last_insert_id();
        $first = $last - $this->lid;



        // (Setting the value)
        $ids = [];

        for ( $i = $first; $i <= $last; $i++ )
        {// Iterating each index
            // (Appending the value)
            $ids[] = $i;
        }



        // Returning the value
        return $ids;
    }

    # Returns [int]
    public function last_id ()
    {
        // Returning the value
        return (int) $this->connection->get_last_insert_id();
    }



    # Returns [self|false]
    public function empty ()
    {
        if ( !$this->connection->execute( "TRUNCATE TABLE `$this->database`.`$this->table`;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [assoc|false]
    public function describe ()
    {
        // Returning the value
        return $this->connection->describe( $this->database, $this->table );
    }



    # Returns [self|false]
    public function copy (string $dst_database, string $dst_table, bool $copy_data = false)
    {
        // (Getting the values)
        $dst_database = str_replace( '`', '', $dst_database );
        $dst_table    = str_replace( '`', '', $dst_table );



        if ( !$this->connection->execute( "CREATE TABLE `$dst_database`.`$dst_table` LIKE `$this->database`.`$this->table`;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        if ( $copy_data )
        {// Value is true
            if ( !$this->connection->execute( "INSERT INTO `$dst_database`.`$dst_table` SELECT * FROM `$this->database`.`$this->table`;" ) )
            {// (Unable to execute the cmd)
                // Returning the value
                return false;
            }
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false]
    public function remove ()
    {
        if ( !$this->connection->execute( "DROP TABLE IF EXISTS `$this->database`.`$this->table`;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function reset ()
    {
        // (Getting the value)
        $this->condition = ( new Condition() )->set_connection( $this->connection )->set_model( $this );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function where ()
    {
        // (Getting the values)
        $args     = func_get_args();
        $num_args = count($args);



        // (Composing the condition)
        $this->condition->where_raw('(');



        switch ( $num_args )
        {
            case 3:// (Format = COV)
                // (Getting the values)
                [ $column, $operator, $value ] = $args;

                // (Composing the condition)
                $this->condition->where_field( null, $column )->op( $operator )->value( $value );
            break;

            case 2:// (Format = CV)
                // (Getting the value)
                [ $column, $value ] = $args;

                // (Composing the condition)
                $this->condition->where_field( null, $column )->op( '=' )->value( $value );
            break;

            case 1:// (Format = COV[] or CV[] or RAW)
                if ( is_array( $args[0] ) )
                {// (Value is an array)
                    // (Getting the value)
                    $num_args = count( $args[0] );

                    for ( $i = 0; $i < $num_args; $i++)
                    {// Iterating each index
                        // (Getting the value)
                        $expr = $args[0][$i];



                        // (Getting the value)
                        $length = count( $expr );

                        switch ( $length )
                        {
                            case 3:// (Format = COV)
                                // (Getting the values)
                                [ $column, $operator, $value ] = $expr;

                                // (Composing the condition)
                                $this->condition->where_field( null, $column )->op( $operator )->value( $value );
                            break;

                            case 2:// (Format = CV)
                                // (Getting the values)
                                [ $column, $value ] = $expr;

                                // (Composing the condition)
                                $this->condition->where_field( null, $column )->op( '=' )->value( $value );
                            break;

                            case 1:// (Format = RAW)
                                // (Composing the condition)
                                $this->condition->where_raw( $expr[0] );
                            break;
                        }



                        if ( $i < $num_args - 1 )
                        {// (Index is not the last)
                            // (Composing the condition)
                            $this->condition->and();
                        }
                    }
                }
                else
                {// (Value is not an array)
                    // (Composing the condition)
                    $this->condition->where_raw( $args[0] );
                }
            break;
        }



        // (Composing the condition)
        $this->condition->where_raw(')');



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function and ()
    {
        // (Composing the condition)
        $this->condition->and();



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function or ()
    {
        // (Composing the condition)
        $this->condition->or();



        // Returning the value
        return $this;
    }

    # Returns [bool]
    public function exists ()
    {
        // Returning the value
        return $this->count() > 0;
    }



    # Returns [self]
    public function bind (&$object, array $fields = [])
    {
        // (Getting the value)
        $object = $this->find( $fields );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function group (array $columns)
    {
        // (Setting the value)
        $this->group_columns = [];

        foreach ( $columns as $column )
        {// Processing each entry
            // (Appending the value)
            $this->group_columns[] = $column;
        }



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function order (array $columns)
    {
        // (Setting the value)
        $this->order_columns = [];

        foreach ( $columns as $column => $direction )
        {// Processing each entry
            switch ( $direction )
            {
                case SORT_ASC:
                    // (Setting the value)
                    $direction = 'ASC';
                break;

                case SORT_DESC:
                    // (Setting the value)
                    $direction = 'DESC';
                break;

                default:
                    // (Getting the value)
                    $direction = strtoupper( $direction );
            }



            // (Getting the value)
            $this->order_columns[ $column ] = $direction;
        }



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function fill (array $values)
    {
        // (Filling the values)
        $this->condition->fill( $values );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function search (string $value, string $format = '%V%', array $fields)
    {
        // (Getting the value)
        $this->condition->search( $value, $format, $fields );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function paginate (int $limit, ?int $offset = null)
    {
        // (Getting the values)
        $this->limit  = $limit;
        $this->offset = $offset;



        // Returning the value
        return $this;
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return "`$this->database`.`$this->table`";
    }
}



?>
<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\QueryBuilder;
use \Solenoid\Vector\Vector;



class QueryRunner
{
    private Connection          $connection;

    private ?string               $database;
    private ?string                  $table;

    private bool                 $auto_type;
    private ?string       $column_separator;

    private ?QueryBuilder    $query_builder;

    private ?string            $query_debug;



    private static array      $schemas = [];



    # Returns [self] | Throws [Exception]
    public function __construct (Connection &$connection, ?string $database = null, ?string $table = null)
    {
        // (Getting the values)
        $this->connection    = $connection;

        $this->database      = $database;
        $this->table         = $table;

        $this->query_builder = ( $this->database && $this->table ) ? QueryBuilder::create( $this->connection, $this->database, $this->table) : null;



        // (Setting the values)
        $this->auto_type        = false;
        $this->column_separator = null;



        // (Getting the value)
        $this->connection->add_event_listener
        (
            'open',
            function ()
            {
                if ( $this->database )
                {// Value found
                    if ( !$this->connection->select_db( $this->database ) )
                    {// (Unable to select the database)
                        // (Setting the value)
                        $message = "Unable to select the database :: " . $this->connection->get_error_text();

                        // Throwing an exception
                        throw new \Exception($message);

                        // Returning the value
                        return;
                    }
                }

                if ( $this->database && $this->table )
                {// Values found
                    if ( $this->auto_type )
                    {// Value is true
                        if ( !self::$schemas[ $this->database ][ $this->table ] )
                        {// Value not found
                            // (Getting the value)
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
                                $message = "Unable to execute the query to get the table schema :: " . $this->connection->get_error_text();

                                // Throwing an exception
                                throw new \Exception($message);

                                // Returning the value
                                return;
                            }



                            // (Getting the value)
                            $columns = $this->connection->fetch_cursor()->to_array();

                            foreach ($columns as $column)
                            {// Processing each entry
                                // (Getting the value)
                                $column_type = preg_replace( '/\([^\)]+\)$/', '', $column['Type'] );

                                switch ( $column_type )
                                {
                                    case 'tinyint':
                                    case 'smallint':
                                    case 'mediumint':
                                    case 'int':
                                    case 'bigint':
                                        // (Setting the value)
                                        $type = 'int';
                                    break;

                                    case 'decimal':
                                    case 'float':
                                    case 'double':
                                    case 'real':
                                        // (Setting the value)
                                        $type = 'float';
                                    break;

                                    default:
                                        // (Setting the value)
                                        $type = 'string';
                                }

                                // (Getting the value)
                                self::$schemas[ $this->database ][ $this->table ][ $column['Field'] ] =
                                [
                                    'type' => $type,
                                    'null' => $column['Null'] !== 'NO'
                                ]
                                ;
                            }
                        }
                    }
                }
            }
        )
        ;



        // (Capturing the query)
        $this->capture();
    }

    # Returns [QueryRunner]
    public static function create (Connection &$connection, ?string $database = null, ?string $table = null)
    {
        // Returning the value
        return new QueryRunner( $connection, $database, $table );
    }



    # Returns [self]
    public function capture (?string &$query = '')
    {
        // (Getting the value)
        $this->query_debug = &$query;



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function set_auto_type (bool $value = true)
    {
        // (Getting the value)
        $this->auto_type = $value;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function set_column_separator (?string $value = '.')
    {
        // (Getting the value)
        $this->column_separator = $value;



        // Returning the value
        return $this;
    }



    # Returns [bool] | Throws [Exception]
    public function insert (array $kv_data = [], array $raw_kv_data = [], bool $ignore_error = false)
    {
        // (Getting the value)
        $keys = array_keys( $kv_data + $raw_kv_data );

        foreach ($keys as $i => $key)
        {// Processing each entry
            // (Getting the value)
            $keys[$i] = str_replace( '`', '', $key );
        }

        // (Getting the value)
        $keys_s = '`' . implode( '`,`', $keys ) . '`';



        // (Setting the value)
        $values_s = [];



        // (Getting the value)
        $values = array_values( $kv_data );

        foreach ($values as $k => $v)
        {// Processing each entry
            if ( $this->connection->get_insert_mode() === 'empty_text_as_null' )
            {// Match OK
                if ( strlen( $v ) === 0 )
                {// Value is empty
                    // (Setting the value)
                    $v = null;
                }
            }



            // (Normalizing the value)
            $nv = $this->connection->normalize_value( $v );

            if ( $nv === false )
            {// (Unable to normalize the value)
                // (Setting the value)
                $message = "Unable to normalize the value";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }



            // (Appending the value)
            $values_s[] = $nv;
        }



        // (Getting the value)
        $values = array_values( $raw_kv_data );

        foreach ($values as $k => $v)
        {// Processing each entry
            // (Appending the value)
            $values_s[] = $v;
        }



        // (Getting the value)
        $values_s = implode( ',', $values_s );



        // (Getting the value)
        $ignore = $ignore_error ? ' IGNORE' : '';

        // (Getting the value)
        $query = "INSERT$ignore\n\tINTO `$this->database`.`$this->table` ($keys_s)\nVALUES\n\t($values_s)\n;";



        if ( !$this->connection->execute( $query, [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // Returning the value
        return true;
    }

    # Returns [bool] | Throws [Exception]
    public function multi_insert (array $kv_data_list = [], bool $ignore_error = false)
    {
        if ( !$kv_data_list )
        {// Value is empty
            // (Setting the value)
            $message = "Cannot insert the records :: Values list is empty";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // (Getting the value)
        $keys = array_keys( $kv_data_list[0] );

        foreach ($keys as $i => $key)
        {// Processing each entry
            // (Getting the value)
            $keys[$i] = str_replace( '`', '', $key );
        }

        // (Getting the value)
        $keys_s = '`' . implode( '`,`', $keys ) . '`';



        // (Setting the value)
        $values_s_list = [];

        foreach ($kv_data_list as $kv_data)
        {// Processing each entry
            // (Getting the value)
            $values = array_values( $kv_data );

            // (Setting the value)
            $values_s = [];

            foreach ($values as $k => $v)
            {// Processing each entry
                if ( $this->connection->get_insert_mode() === 'empty_text_as_null' )
                {// Match OK
                    if ( strlen( $v ) === 0 )
                    {// Value is empty
                        // (Setting the value)
                        $v = null;
                    }
                }



                // (Normalizing the value)
                $nv = $this->connection->normalize_value( $v );

                if ( $nv === false )
                {// (Unable to normalize the value)
                    // (Setting the value)
                    $message = "Unable to normalize the value";

                    // Throwing an exception
                    throw new \Exception($message);

                    // Returning the value
                    return false;
                }



                // (Appending the value)
                $values_s[] = $nv;
            }

            // (Getting the value)
            $values_s = implode( ',', $values_s );



            // (Appending the value)
            $values_s_list[] = $values_s;
        }



        // (Getting the value)
        $ignore = $ignore_error ? ' IGNORE' : '';

        // (Getting the value)
        $values_s_list_s = array_map( function ($entry) { return "($entry)"; }, $values_s_list );
        $values_s_list_s = implode( ",\n\t", $values_s_list_s );

        // (Getting the query)
        $query = "INSERT$ignore\n\tINTO `$this->database`.`$this->table` ($keys_s)\nVALUES\n\t$values_s_list_s\n;";



        if ( !$this->connection->execute( $query, [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // Returning the value
        return true;
    }

    # Returns [bool] | Throws [Exception]
    public function update (array $kv_data = [], array $raw_kv_data = [])
    {
        // (Setting the value)
        $columns = [];

        foreach ($kv_data as $k => $v)
        {// Processing each entry
            // (Getting the value)
            $k = str_replace( '`', '', $k );



            // (Normalizing the value)
            $value = $this->connection->normalize_value( $v );

            if ( $value === false )
            {// (Unable to normalize the value)
                // (Setting the value)
                $message = "Unable to normalize the value";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }



            // (Appending the value)
            $columns[] = "`$k` = $value";
        }

        foreach ($raw_kv_data as $k => $v)
        {// Processing each entry
            // (Getting the value)
            $k = str_replace( '`', '', $k );



            // (Getting the value)
            $value = $v;



            // (Appending the value)
            $columns[] = "`$k` = $value";
        }

        // (Getting the value)
        $columns = implode( ', ', $columns );



        // (Getting the value)
        $selection = $this->query_builder->build_selection();



        // (Setting the value)
        $components = [];



        // (Appending the value)
        $components[] = "UPDATE\n\t`$this->database`.`$this->table`";
        $components[] = "SET\n\t$columns";



        if ( $selection !== '1' )
        {// (Selection has been defined)
            // (Appending the value)
            $components[] = "WHERE\n\t$selection";
        }



        // (Getting the query)
        $query = implode( "\n", $components ) . "\n;";



        if ( !$this->connection->execute( $query, [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value found
            // Returning the value
            return true;
        }



        // Returning the value
        return true;
    }

    # Returns [Cursor|false] | Throws [Exception]
    public function set (array $kv_data = [], array $raw_kv_data = [], array $key = [], bool $ignore_error = false)
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
            if ( $kv_data[ $key_component ] )
            {// Value found
                // (Getting the value)
                $key_values['normalized'][ $key_component ] = $kv_data[ $key_component ];
            }
            else
            if ( $raw_kv_data[ $key_component ] )
            {// Value found
                // (Getting the value)
                $key_values['raw'][ $key_component ] = $raw_kv_data[ $key_component ];
            }
        }



        // (Getting the value)
        $where_kv_data = $key_values['normalized'] + $key_values['raw'];



        // (Getting the value)
        $cursor = QueryRunner::create( $this->connection, $this->database, $this->table, $this->auto_type )->where_list( $where_kv_data )->select();

        if ( $cursor->is_empty() )
        {// (Record not found)
            if ( !QueryRunner::create( $this->connection, $this->database, $this->table, $this->auto_type )->capture( $this->query_debug )->insert( $kv_data, $raw_kv_data, $ignore_error ) )
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
            // (Getting the values)
            $n_kv_data = array_diff_assoc( $kv_data, $key_values['normalized'] );
            $r_kv_data = array_diff_assoc( $raw_kv_data, $key_values['raw'] );

            if ( !QueryRunner::create( $this->connection, $this->database, $this->table, $this->auto_type )->capture( $this->query_debug )->where_list( $key_values['normalized'] + $key_values['raw'] )->update( $n_kv_data, $r_kv_data ) )
            {// (Unable to update the record)
                // (Setting the value)
                $message = "Unable to update the record :: " . $this->connection->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // (Getting the value)
        $cursor = QueryRunner::create( $this->connection, $this->database, $this->table, $this->auto_type )->where_list( $where_kv_data )->select();



        // Returning the value
        return $cursor;
    }

    # Returns [bool] | Throws [Exception]
    public function delete (bool $ignore_foreign_key_check = false)
    {
        if ( !$this->query_debug )
        {// Value not found
            if ( $ignore_foreign_key_check )
            {// Value is true
                if ( $this->connection->set_foreign_key_check( false ) === false )
                {// (Unable to disable the foreign key check)
                    // (Setting the value)
                    $message = "Unable to disable the foreign key check :: " . $this->connection->get_error_text();

                    // Throwing an exception
                    throw new \Exception($message);

                    // Returning the value
                    return false;
                }
            }
        }






        // (Building the query)
        $query = $this->query_builder->build_delete();



        if ( !$this->connection->execute( $query, [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }






        if ( !$this->query_debug )
        {// Value not found
            if ( $ignore_foreign_key_check )
            {// Value is true
                if ( $this->connection->set_foreign_key_check( true ) === false )
                {// (Unable to enable the foreign key check)
                    // (Setting the value)
                    $message = "Unable to enable the foreign key check :: " . $this->connection->get_error_text();

                    // Throwing an exception
                    throw new \Exception($message);

                    // Returning the value
                    return false;
                }
            }
        }



        // Returning the value
        return true;
    }



    # Returns [self|false] | Throws [Exception]
    public function truncate (bool $ignore_foreign_key_check = false)
    {
        if ( !$this->query_debug )
        {// Value not found
            if ( $ignore_foreign_key_check )
            {// Value is true
                if ( $this->connection->set_foreign_key_check( false ) === false )
                {// (Unable to disable the foreign key check)
                    // (Setting the value)
                    $message = "Unable to disable the foreign key check :: " . $this->connection->get_error_text();

                    // Throwing an exception
                    throw new \Exception($message);

                    // Returning the value
                    return false;
                }
            }
        }






        if ( !$this->connection->execute( "TRUNCATE TABLE `$this->database`.`$this->table`;", [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }






        if ( !$this->query_debug )
        {// Value not found
            if ( $ignore_foreign_key_check )
            {// Value is true
                if ( $this->connection->set_foreign_key_check( true ) === false )
                {// (Unable to enable the foreign key check)
                    // (Setting the value)
                    $message = "Unable to enable the foreign key check :: " . $this->connection->get_error_text();

                    // Throwing an exception
                    throw new \Exception($message);

                    // Returning the value
                    return false;
                }
            }
        }
        


        // Returning the value
        return $this;
    }



    # Returns [Cursor|false] | Throws [Exception]
    public function find (array $kv_data = [], array $raw_kv_data = [], bool $unique = false, array $columns = [])
    {
        // (Getting the value)
        $projection = $columns ? implode( ', ', array_map( function ($column) { $column = str_replace( '`', '', $column ); return "`$column`"; }, $columns ) ) : '*';



        // (Setting the value)
        $selection = [];

        foreach ($kv_data as $k => $v)
        {// Processing each entry
            // (Getting the value)
            $k = str_replace( '`', '', $k );



            // (Normalizing the value)
            $nv = $this->connection->normalize_value( $v );

            if ( $nv === false )
            {// (Unable to normalize the value)
                // (Setting the value)
                $message = "Unable to normalize the value";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }



            // (Appending the value)
            $selection[] = "( `$k` = $nv )";
        }

        foreach ($raw_kv_data as $k => $v)
        {// Processing each entry
            // (Getting the value)
            $k = str_replace( '`', '', $k );



            // (Appending the value)
            $selection[] = "( `$k` = $v )";
        }

        // (Getting the value)
        $selection = implode( ' AND ', $selection );
        $selection = $selection ? $selection : 1;



        // (Getting the value)
        $distinct = $unique ? ' DISTINCT' : '';



        // (Getting the value)
        $query =
        <<<EOQ
        SELECT$distinct
        \t$projection
        FROM
        \t`$this->database`.`$this->table` T
        WHERE
        \t$selection
        ;
        EOQ
        ;

        if ( !$this->connection->execute( $query, [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // Returning the value
        return $this->connection->fetch_cursor();
    }



    # Returns [self|false] | Throws [Exception]
    public function where (string $key, string $operator, $value, bool $raw_value = false)
    {
        if ( !$this->query_builder->add_selection( $key, $operator, $value, $raw_value ) )
        {// (Unable to add the selection)
            // (Setting the value)
            $message = "Unable to add the selection";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false] | Throws [Exception]
    public function where_raw (string $selection, array $kv_data = [])
    {
        if ( !$this->query_builder->add_raw_selection( $selection, $kv_data ) )
        {// (Unable to add the raw selection)
            // (Setting the value)
            $message = "Unable to add the raw selection";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function where_list (array $kv_data, bool $raw = false)
    {
        // (Getting the value)
        $num_keys = count( array_keys( $kv_data ) );

        // (Setting the value)
        $i = 0;



        foreach ($kv_data as $k => $v)
        {// Processing each entry
            // (Incrementing the value)
            $i += 1;



            if ( $raw )
            {// Value is true
                // (Calling the function)
                $this->where_raw( $v );
            }
            else
            {// Value is false
                // (Calling the function)
                $this->where( $k, '=', $v );
            }



            if ( $i < $num_keys )
            {// (Index is not the last)
                // (Calling the function)
                $this->w_and();
            }
        }



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function w_and ()
    {
        // (Adding the AND to the selection)
        $this->query_builder->add_selection_and();



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function w_or ()
    {
        // (Adding the OR to the selection)
        $this->query_builder->add_selection_or();



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function w_expr (string $value)
    {
        // (Adding the custom value to the selection)
        $this->query_builder->add_selection_custom_value( $value );



        // Returning the value
        return $this;
    }



    # Returns [self|false] | Throws [Exception]
    public function having_raw (string $selection)
    {
        if ( !($this->query_builder->add_group_selection( $selection ) ) )
        {// (Unable to add the group selection)
            // (Setting the value)
            $message = "Unable to add the group selection";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [void]
    public function h_and ()
    {
        // (Adding the AND to the selection)
        $this->query_builder->add_group_selection_and();



        // Returning the value
        return $this;
    }

    # Returns [void]
    public function h_or ()
    {
        // (Adding the OR to the selection)
        $this->query_builder->add_group_selection_or();



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function group_by (array $columns)
    {
        // (Setting the columns for the group)
        $this->query_builder->set_group_columns( $columns );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function order_by (array $columns)
    {
        foreach ($columns as $column => $order_direction)
        {// Processing each entry
            // (Adding the column for the order)
            $this->query_builder->add_order_column( $column, $order_direction );
        }



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function limit (int $size, ?int $offset = null)
    {
        // (Setting the limit)
        $this->query_builder->set_limit( $size, $offset );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function range_limit (int $start, int $end, ?int $offset = null)
    {
        // (Setting the limit)
        $this->query_builder->set_range_limit( $start, $end, $offset );



        // Returning the value
        return $this;
    }



    # Returns [float|bool] | Throws [Exception]
    public function count (?string $value = null, ?bool $raw_value = false)
    {
        if ( $value === null )
        {// Value not found
            // (Setting the values)
            $value     = '*';
            $raw_value = true;
        }



        // (Getting the value)
        $columns = [ $this->query_builder->build_aggregator( 'COUNT', $value, $raw_value ) . ' AS `value`' ];

        // (Building the query)
        $query = $this->query_builder->build_select( $columns );



        if ( !$this->connection->execute( $query, [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query to count the records";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // (Getting the value)
        $value = $this->connection->fetch_cursor()->set_mode('value')->fetch_head();



        // Returning the value
        return (float) $value;
    }



    # Returns [float|bool] | Throws [Exception]
    public function calculate (string $function, string $column)
    {
        // (Getting the value)
        $columns = [ $this->query_builder->build_aggregator( $function, $column ) . ' AS `value`' ];

        // (Building the query)
        $query = $this->query_builder->build_select( $columns );



        if ( !$this->connection->execute( $query, [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query to calculate the function of the values";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // (Getting the value)
        $value = $this->connection->fetch_cursor()->set_mode('value')->fetch_head();



        // Returning the value
        return (float) $value;
    }



    # Returns [Cursor|bool] | Throws [Exception]
    public function select (array $columns = [], bool $unique = false, bool $raw_columns = false)
    {
        // (Setting the value)
        $projection_columns = [];

        if ( $raw_columns )
        {// (Values are not manipulated)
            foreach ($columns as $entry)
            {// Processing each entry
                // (Appending the value)
                $projection_columns[] = $entry;
            }
        }
        else
        {// (Values are manipulated)
            if ( Vector::create( $columns )->is_sequential() )
            {// (Array is sequential)
                foreach ($columns as $column)
                {// Processing each entry
                    // (Getting the value)
                    $column = str_replace( '`', '', $column );

                    // (Appending the value)
                    $projection_columns[] = "`$column`";
                }
            }
            else
            {// (Array is associative)
                foreach ($columns as $column => $label)
                {// Processing each entry
                    // (Getting the values)
                    $column = str_replace( '`', '', $column );
                    $label  = str_replace( '`', '', $label );

                    // (Appending the value)
                    $projection_columns[] = "`$column` AS `$label`";
                }
            }
        }



        // (Building the query)
        $query = $this->query_builder->build_select( $projection_columns, $unique );



        if ( !$this->connection->execute( $query, [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // (Fetching the cursor)
        $cursor = $this->connection->fetch_cursor
        (
            self::$schemas[ $this->database ][ $this->table ] ?? null,
            $this->column_separator
        )
        ;

        if ( $cursor->set_mode( count( $columns ) === 1 ? 'value' : 'record' ) === false )
        {// (Unable to set the cursor mode)
            // (Setting the value)
            $message = "Unable to set the cursor mode";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $cursor;
    }

    # Returns [Cursor|bool] | Throws [Exception]
    public function raw (string $query, array $kv_data = [])
    {
        if ( !$this->connection->execute( $query, $kv_data, $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // Returning the value
        return $this->connection->fetch_cursor( null, $this->column_separator );
    }



    # Returns [string]
    public function get_error_text ()
    {
        // Returning the value
        return $this->connection->get_error_text();
    }

    # Returns [void]
    public function reset ()
    {
        // (Resetting the QueryBuilder)
        $this->query_builder->reset();
    }



    # Returns [self|bool] | Throws [Exception]
    public function start_transaction ()
    {
        if ( !$this->connection->execute( 'START TRANSACTION;', [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|bool] | Throws [Exception]
    public function commit ()
    {
        if ( !$this->connection->execute( 'COMMIT;', [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false] | Throws [Exception]
    public function rollback ()
    {
        if ( !$this->connection->execute( 'ROLLBACK;', [], $this->query_debug ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $this->query_debug )
        {// Value is true
            // Returning the value
            return true;
        }



        // Returning the value
        return $this;
    }



    # Returns [self] | Throws [Exception]
    public function filter (array $filters)
    {
        // (Getting the value)
        $filters_length = count($filters);

        for ($i = 0; $i < $filters_length; $i++)
        {// Iterating each index
            // (Composing the query runner)
            $this->where_list( $filters[$i] );

            if ( $i < $filters_length - 1 )
            {// Match OK
                // (Composing the query runner)
                $this->w_or();
            }
        }



        // Returning the value
        return $this;
    }
}



?>
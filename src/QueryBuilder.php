<?php



namespace Solenoid\MySQL;



class QueryBuilder
{
    private Connection     $connection;

    private string           $database;
    private string              $table;

    private string          $selection;

    private array       $group_columns;
    private string    $group_selection;

    private array       $order_columns;

    private string              $limit;



    # Returns [self]
    public function __construct (Connection &$connection, string $database, string $table)
    {
        // (Getting the values)
        $this->connection = $connection;

        $this->database   = str_replace( '`', '', $database );
        $this->table      = str_replace( '`', '', $table );



        // (Resetting the values)
        $this->reset();
    }

    # Returns [QueryBuilder]
    public static function create (Connection &$connection, string $database, string $table)
    {
        // Returning the value
        return new QueryBuilder( $connection, $database, $table );
    }



    # Returns [void]
    public function reset ()
    {
        // (Setting the values)
        $this->selection       = '';
        $this->group_selection = '';

        $this->limit           = '';

        $this->group_columns   = [];
        $this->order_columns   = [];
    }



    # Returns [bool] | Throws [Exception]
    public function add_selection (string $key, string $operator, $value, bool $raw_value = false)
    {
        // (Getting the value)
        $key = str_replace( '`', '', $key );



        if ( !$raw_value )
        {// (Value is manipulated)
            switch ( strtoupper( $operator ) )
            {
                case 'IS':
                case 'IS NOT':
                    if ( gettype( $value ) === 'string' )
                    {// Match OK
                        switch ( strtolower( $value ) )
                        {
                            case 'null':
                                // (Setting the value)
                                $value = null;
                            break;

                            case 'false':
                                // (Setting the value)
                                $value = false;
                            break;

                            case 'true':
                                // (Setting the value)
                                $value = true;
                            break;
                        }
                    }



                    // (Normalizing the value)
                    $value = $this->connection->normalize_value( $value );

                    if ( $value === false )
                    {// (Unable to normalize the value)
                        // (Setting the value)
                        $message = "Unable to normalize the value";

                        // Throwing an exception
                        throw new \Exception($message);

                        // Returning the value
                        return false;
                    }
                break;

                case 'IN':
                case 'NOT IN':
                    // (Setting the value)
                    $values = [];

                    foreach ($value as $entry)
                    {// Processing each entry
                        // (Getting the value)
                        $normalized_value = $this->connection->normalize_value( $entry );

                        if ( $normalized_value === false )
                        {// (Unable to normalize the value)
                            // (Setting the value)
                            $message = "Unable to normalize the value";
    
                            // Throwing an exception
                            throw new \Exception($message);
    
                            // Returning the value
                            return false;
                        }



                        // (Appending the value)
                        $values[] = $normalized_value;
                    }



                    // (Getting the value)
                    $value = '( ' . implode( ',', $values ) . ' )';
                break;

                default:
                    // (Normalizing the value)
                    $value = $this->connection->normalize_value( $value );

                    if ( $value === false )
                    {// (Unable to normalize the value)
                        // (Setting the value)
                        $message = "Unable to normalize the value";

                        // Throwing an exception
                        throw new \Exception($message);

                        // Returning the value
                        return false;
                    }
            }
        }



        // (Appending the value)
        $this->selection .= "( `$key` $operator $value )";



        // Returning the value
        return true;
    }

    # Returns [bool] | Throws [Exception]
    public function add_raw_selection (string $selection, array $kv_data = [])
    {
        // (Filling the variables)
        $selection = $this->connection->fill_vars( $selection, $kv_data );

        if ( $selection === false )
        {// (Unable to fill the variables)
            // (Setting the value)
            $message = "Unable to fill the variables";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // (Appending the value)
        $this->selection .= "( $selection )";



        // Returning the value
        return true;
    }

    # Returns [void]
    public function add_selection_and ()
    {
        if ( !$this->selection )
        {// Value is empty
            // Returning the value
            return;
        }



        // (Appending the value)
        $this->selection .= ' AND ';
    }

    # Returns [void]
    public function add_selection_or ()
    {
        if ( !$this->selection )
        {// Value is empty
            // Returning the value
            return;
        }



        // (Appending the value)
        $this->selection .= ' OR ';
    }

    # Returns [void]
    public function add_selection_custom_value (string $value)
    {
        // (Appending the value)
        $this->selection .= " $value ";
    }



    # TO IMPLEMENT
    public function add_group_selection ()
    {
        
    }

    # Returns [void]
    public function add_group_raw_selection (string $selection)
    {
        // (Appending the value)
        $this->group_selection .= "( $selection )";
    }

    # Returns [void]
    public function add_group_selection_and ()
    {
        if ( !$this->group_selection )
        {// Value is empty
            // Returning the value
            return;
        }



        // (Appending the value)
        $this->group_selection .= ' AND ';
    }

    # Returns [void]
    public function add_group_selection_or ()
    {
        if ( !$this->group_selection )
        {// Value is empty
            // Returning the value
            return;
        }



        // (Appending the value)
        $this->group_selection .= ' OR ';
    }



    # Returns [void]
    public function add_order_column (string $column, string $order_direction = 'ASC')
    {
        // (Getting the values)
        $column          = str_replace( '`', '', $column );
        $order_direction = strtoupper( $order_direction );



        // (Appending the value)
        $this->order_columns[] = "`$column` $order_direction";
    }

    # Returns [void]
    public function set_group_columns (array $columns)
    {
        // (Setting the value)
        $group_columns = [];

        foreach ($columns as $column)
        {// Processing each entry
            // (Getting the value)
            $column = str_replace( '`', '', $column );



            // (Appending the value)
            $group_columns[] = "`$column`";
        }



        // (Getting the value)
        $this->group_columns = $group_columns;
    }



    # Returns [void]
    public function set_limit (int $size, ?int $offset = null)
    {
        // (Getting the value)
        $this->limit = "LIMIT $size" . ( $offset === null ? '' : " OFFSET $offset" );
    }

    # Returns [void]
    public function set_range_limit (int $start, int $end, ?int $offset = null)
    {
        // (Getting the value)
        $this->limit = "LIMIT $start,$end" . ( $offset === null ? '' : " OFFSET $offset" );
    }



    # Returns [string]
    public function build_group ()
    {
        // Returning the value
        return implode( ', ', $this->group_columns );
    }
    
    # Returns [string]
    public function build_order ()
    {
        // Returning the value
        return implode( ', ', $this->order_columns );
    }

    # Returns [string]
    public function build_limit ()
    {
        // Returning the value
        return $this->limit;
    }

    # Returns [string]
    public function build_aggregator (string $function_name, string $value, bool $raw_value = false)
    {
        if ( !$raw_value )
        {// (Key is manipulated)
            // (Getting the value)
            $value = str_replace( '`', '', $value );
            $value = "`$value`";
        }



        // Returning the value
        return "$function_name($value)";
    }

    # Returns [string]
    public function build_projection (array $columns = [])
    {
        // Returning the value
        return $columns ? implode( ', ', $columns ) : '*';
    }

    # Returns [string]
    public function build_selection ()
    {
        // Returning the value
        return $this->selection === '' ? '1' : $this->selection;
    }



    # Returns [string]
    public function build_fwgol ()
    {
        // (Setting the value)
        $components = [];



        // (Appending the value)
        $components[] = "FROM\n\t`$this->database`.`$this->table` T";



        // (Getting the value)
        $selection = $this->build_selection();

        if ( $selection !== '1' )
        {// (Selection has been defined)
            // (Appending the value)
            $components[] = "WHERE\n\t$selection";
        }



        // (Getting the value)
        $group_by = $this->build_group();

        if ( $group_by )
        {// (Group-By has been defined)
            // (Appending the value)
            $components[] = "GROUP BY\n\t$group_by";
        }



        // (Getting the value)
        $order_by = $this->build_order();

        if ( $order_by )
        {// (Order-By has been defined)
            // (Appending the value)
            $components[] = "ORDER BY\n\t$order_by";
        }



        // (Getting the value)
        $limit = $this->build_limit();

        if ( $limit )
        {// (Limit has been defined)
            // (Appending the value)
            $components[] = $limit;
        }



        // Returning the value
        return implode( "\n", $components );
    }

    # Returns [string]
    public function build_delete ()
    {
        // (Setting the value)
        $components = [];



        // (Appending the value)
        $components[] = "DELETE\n\t";
        $components[] = "FROM\n\t`$this->database`.`$this->table`";



        // (Getting the value)
        $selection = $this->build_selection();

        if ( $selection !== '1' )
        {// (Selection has been defined)
            // (Appending the value)
            $components[] = "WHERE\n\t" . $selection;
        }



        // Returning the value
        return implode( "\n", $components ) . "\n;";
    }

    # Returns [string]
    public function build_select (array $columns = [], bool $unique = false)
    {
        // (Getting the values)
        $projection      = $this->build_projection( $columns );
        $fwgol           = $this->build_fwgol();

        $unique_operator = $unique ? ' DISTINCT' : '';



        // Returning the value
        return "SELECT$unique_operator\n\t$projection\n$fwgol\n;";
    }



    # Returns [string]
    public function get_selection ()
    {
        // Returning the value
        return $this->selection;
    }
}



?>
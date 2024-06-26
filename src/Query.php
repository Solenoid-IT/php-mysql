<?php



namespace Solenoid\MySQL;



class Query
{
    private Connection $connection;
    private ?string    $name;

    private string     $source;
    private string     $condition;
    private array      $group;
    private array      $order;
    private string     $limit;

    private array      $projection;
    private bool       $distinct;



    # Returns [self]
    public function __construct (Connection &$connection, ?string $name = null)
    {
        // (Getting the values)
        $this->connection = &$connection;
        $this->name       = $name;



        // (Setting the values)
        $this->source     = '';
        $this->condition  = '';
        $this->group      = [];
        $this->order      = [];
        $this->limit      = '';

        $this->projection = [];
        $this->distinct   = false;
    }



    # Returns [self]
    public function from_raw (string $content)
    {
        // (Appending the value)
        $this->source .= $content;



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function from (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ( $database ? '`' . $this->connection->sanitize_text($database) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text($table) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function natural_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' NATURAL JOIN ' . ( $database ? '`' . $this->connection->sanitize_text($database) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text($table) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function cross_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' CROSS JOIN ' . ( $database ? '`' . $this->connection->sanitize_text($database) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text($table) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function inner_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' INNER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text($database) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text($table) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function left_outer_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' LEFT OUTER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text($database) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text($table) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function right_outer_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' RIGHT OUTER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text($database) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text($table) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function on (string $a, string $op, string $b)
    {
        // (Appending the value)
        $this->from_raw( '`' . $this->connection->sanitize_text($a) . '`' . ' ' . $op . ' ' . '`' . $this->connection->sanitize_text($b) . '`' );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function on_and ()
    {
        // (Appending the value)
        $this->from_raw( ' AND ' );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function where_raw (string $content)
    {
        // (Appending the value)
        $this->condition .= $content;



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function where (string $value, bool $raw = false)
    {
        if ( !$raw )
        {// (Subject is not raw)
            // (Getting the value)
            $value = $this->connection->normalize_value($value);
        }



        // (Appending the value)
        $this->where_raw($value);



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function where_column (?string $alias = null, string $column)
    {
        // (Appending the value)
        $this->where_raw( ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text($column) . '`' );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function op (string $operator)
    {
        // (Appending the value)
        $this->where_raw(" $operator ");



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function value (mixed $value, bool $raw = false)
    {
        if ( !$raw )
        {// (Value is not raw)
            // (Getting the value)
            $value = $this->connection->normalize_value($value);
        }



        // (Appending the value)
        $this->where_raw($value);



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function in (array $values, bool $raw = false)
    {
        if ( !$raw )
        {// (Values are not raw)
            foreach ( $values as &$value )
            {// Processing each entry
                // (Getting the value)
                $value = $this->connection->normalize_value($value);
            }
        }


        
        // (Appending the value)
        $this->where_raw( ' IN ( ' . implode( ', ', $values ) . ' )' );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function not_in (array $values, bool $raw = false)
    {
        if ( !$raw )
        {// (Values are not raw)
            foreach ( $values as &$value )
            {// Processing each entry
                // (Getting the value)
                $value = $this->connection->normalize_value($value);
            }
        }


        
        // (Appending the value)
        $this->where_raw( ' NOT IN ( ' . implode( ', ', $values ) . ' )' );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function and ()
    {
        // Returning the value
        return $this->where_raw(' AND ');
    }

    # Returns [self]
    public function or ()
    {
        // Returning the value
        return $this->where_raw(' OR ');
    }



    # Returns [self]
    public function group_by (?string $alias = null, string $column)
    {
        // (Appending the value)
        $this->group[] = ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text($column) . '`';



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function order_by (?string $alias = null, string $column, string $direction)
    {
        // (Appending the value)
        $this->order[] = ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text($column) . '`' . ( $direction === 'ASC' ? 'ASC' : 'DESC' );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function limit (int $value)
    {
        // (Appending the value)
        $this->limit .= 'LIMIT ' . $value;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function limit_range (int $min, int $max)
    {
        // (Appending the value)
        $this->limit .= 'LIMIT ' . $min . ', ' . $max;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function offset (int $value)
    {
        // (Appending the value)
        $this->limit .= ' OFFSET ' . $value;



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function distinct ()
    {
        // (Setting the value)
        $this->distinct = true;



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function select_raw (string $content)
    {
        // (Appending the value)
        $this->projection[] = $content;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function select (?string $alias = null, string $column, ?string $name = null)
    {
        // (Appending the value)
        $this->select_raw( ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text($column) . '`' . ( $name ? ' AS ' . '`' . $this->connection->sanitize_text($name) . '`' : '' ) );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function select_all (?string $alias = null)
    {
        // (Appending the value)
        $this->select_raw( ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '*' );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function select_agg (string $type, ?string $alias = null, string $column, ?string $name = null)
    {
        // (Appending the value)
        $this->select_raw( $type . '( ' . ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text($column) . '`' . ' ) ' . ( $name ? ' AS ' . '`' . $this->connection->sanitize_text($name) . '`' : '' ) );



        // Returning the value
        return $this;
    }



    # Returns [Cursor|false] | Throws [Exception]
    public function run ()
    {
        if ( !$this->connection->execute( $this ) )
        {// (Unable to execute the query)
            // (Setting the value)
            $message = "Unable to execute the query '$this->name' :: " . $this->connection->get_error_text();

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this->connection->fetch_cursor();
    }



    # Returns [string]
    public function __toString ()
    {
        // (Getting the values)
        $projection = implode( ",\n\t", $this->projection );
        $distinct   = $this->distinct ? 'DISTINCT' : '';
        $source     = $this->source;
        $condition  = $this->condition ? $this->condition : '1';

        $group_by   = $this->group ? implode( ",\n\t", $this->group ) : '';
        $order_by   = $this->order ? implode( ",\n\t", $this->order ) : '';

        $limit      = $this->limit;



        // Returning the value
        return
            <<<EOD
            SELECT $distinct
                $projection
            FROM
                $source
            WHERE
                $condition
            $group_by
            $order_by
            $limit
            ;
            EOD
        ;
    }
}



?>
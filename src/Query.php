<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Condition;



class Query
{
    private Connection $connection;
    private ?string    $name;

    private string     $source;
    private Condition  $condition;
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
    public function from (?string $database = null, string $table, ?string $alias = null, bool $replace = false)
    {
        // (Getting the value)
        $content = ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' );

        if ( $replace )
        {// Value is true
            // (Getting the value)
            $this->source = $content;
        }
        else
        {// Value is false
            // (Appending the value)
            $this->from_raw( $content );
        }



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function natural_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' NATURAL JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function cross_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' CROSS JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function inner_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' INNER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function left_outer_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' LEFT OUTER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function right_outer_join (?string $database = null, string $table, ?string $alias = null)
    {
        // (Appending the value)
        $this->from_raw( ' RIGHT OUTER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function on (string $a, string $op, string $b)
    {
        // (Appending the value)
        $this->from_raw( '`' . $this->connection->sanitize_text( str_replace( '`', '', $a ) ) . '`' . ' ' . $op . ' ' . '`' . $this->connection->sanitize_text( str_replace( '`', '', $b ) ) . '`' );



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
    public function condition (?Condition $condition = null)
    {
        // (Getting the value)
        $this->condition  = $condition ?? new Condition( $this->connection );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function group_by (?string $alias = null, string $column)
    {
        // (Appending the value)
        $this->group[] = ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column ) ) . '`';



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function order_by (?string $alias = null, string $column, string $direction)
    {
        // (Appending the value)
        $this->order[] = ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column ) ) . '`' . ( $direction === 'ASC' ? 'ASC' : 'DESC' );



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
        $this->select_raw( ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column ) ) . '`' . ( $name ? ' AS ' . '`' . $this->connection->sanitize_text( str_replace( '`', '', $name ) ) . '`' : '' ) );



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
        $this->select_raw( $type . '( ' . ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column ) ) . '`' . ' ) ' . ( $name ? ' AS ' . '`' . $this->connection->sanitize_text( str_replace( '`', '', $name ) ) . '`' : '' ) );



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
        $condition  = $this->condition;

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
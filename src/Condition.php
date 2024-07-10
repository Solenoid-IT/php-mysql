<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Query;



class Condition
{
    private Connection $connection;
    private string     $value;



    public Query       $query;



    # Returns [self]
    public function __construct (Connection &$connection)
    {
        // (Getting the value)
        $this->connection = &$connection;



        // (Setting the value)
        $this->value = '';
    }



    # Returns [self]
    public function where_raw (string $content)
    {
        // (Appending the value)
        $this->value .= $content;



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
        $this->where_raw( ( $alias ? $this->connection->sanitize_text($alias) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column) ) . '`' );



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
    public function where_list (array $values)
    {
        // (Getting the value)
        $num_columns = count( array_values( $values ) );



        // (Setting the value)
        $counter = 0;

        foreach ( $values as $k => $v )
        {// Processing each entry
            // (Incrementing the value)
            $counter += 1;



            // (Composing the query)
            $this->where_column( null, $k )->op('=')->value($v);

            if ( $counter < $num_columns - 1 )
            {// (Column is not the last)
                // (Composing the query)
                $this->and();
            }
        }



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



    # Returns [Query]
    public function condition_end ()
    {
        // Returning the value
        return $this->query;
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return $this->value === '' ? '1' : $this->value;
    }
}



?>
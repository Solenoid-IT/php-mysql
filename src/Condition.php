<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Query;
use \Solenoid\MySQL\Model;



class Condition
{
    private string     $value;

    private string     $current_op;



    public Connection  $connection;
    public Query       $query;
    public Model       $model;



    # Returns [self]
    public function __construct ()
    {
        // (Setting the value)
        $this->value = '';
    }



    # Returns [self]
    public function set_connection (Connection &$connection)
    {
        // (Getting the value)
        $this->connection = &$connection;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function set_query (Query &$query)
    {
        // (Getting the value)
        $this->query = &$query;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function set_model (Model &$model)
    {
        // (Getting the value)
        $this->model = &$model;



        // Returning the value
        return $this;
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
    public function where_field (?string $table_alias = null, string $column)
    {
        // (Appending the value)
        $this->where_raw( ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column) ) . '`' );



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function op (string $operator)
    {
        // (Getting the value)
        $this->current_op = $operator;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function value (mixed $value, bool $raw = false)
    {
        if ( !$raw )
        {// (Value is not raw)
            if ( is_array( $value ) )
            {// Value is an array
                // (Getting the value)
                $value = '(' . implode( ',', array_map( function ($entry) { return $this->connection->normalize_value( $entry ); }, $value ) ) . ')';
            }
            else
            {// (Value is not an array)
                if ( $value === null )
                {// Match OK
                    if ( $this->current_op === '=' )
                    {// Match OK
                        // (Setting the value)
                        $this->current_op = 'IS';
                    }
                }



                // (Getting the value)
                $value = $this->connection->normalize_value( $value );
            }
        }



        // (Appending the value)
        $this->where_raw( ' ' . $this->current_op . ' ' );

        // (Setting the value)
        $this->current_op = '';



        // (Appending the value)
        $this->where_raw( $value );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function filter (array $value)
    {
        // (Getting the value)
        $num_x = count( $value );



        // (Setting the value)
        $x = 0;

        foreach ( $value as $k => $v )
        {// Processing each entry
            // (Incrementing the value)
            $x += 1;



            // (Composing the condition)
            $this->where_raw('( ');



            // (Getting the value)
            $num_y = count( array_values( $v ) );



            // (Setting the value)
            $y = 0;

            foreach ( $v as $kk => $vv )
            {// Processing each entry
                // (Incrementing the value)
                $y += 1;



                // (Composing the condition)
                $this->where_field( null, $kk  )->op('=')->value( $vv );

                if ( $y < $num_y - 1 )
                {// (Y is not the last one)
                    // (Composing the condition)
                    $this->and();
                }
            }



            // (Composing the condition)
            $this->where_raw(' )');



            if ( $x < $num_x - 1 )
            {// (X is not the last)
                // (Composing the condition)
                $this->or();
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
    public function is (mixed $value)
    {
        // (Composing the query)
        $this->op('IS')->value($value);



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function is_not (mixed $value)
    {
        // (Composing the query)
        $this->op('IS NOT')->value($value);



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function equal (mixed $value)
    {
        // (Composing the query)
        $this->op('=')->value($value);



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function not_equal (mixed $value)
    {
        // (Composing the query)
        $this->op('<>')->value($value);



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function lt (mixed $value)
    {
        // (Composing the query)
        $this->op('<')->value($value);



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function gt (mixed $value)
    {
        // (Composing the query)
        $this->op('>')->value($value);



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function like (string $start_wildcard = '%', string $value, string $end_wildcard = '%')
    {
        // (Composing the query)
        $this->op('LIKE')->value( "'" . $start_wildcard . $this->connection->sanitize_text( $value ) . $end_wildcard . "'", true );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function search (string $value, string $format = '%V%', array $fields)
    {
        // (Getting the value)
        $num_fields = count( $fields );



        // (Composing the condition)
        $this->where_raw( '( ' );



        foreach ( $fields as $i => $field )
        {// Processing each entry
            if ( is_array( $field ) )
            {// (Value is an array)
                // (Getting the values)
                $table_alias = $field[0];
                $field       = $field[1];
            }
            else
            if ( is_string( $field ) )
            {// (Value is a string)
                // (Setting the value)
                $table_alias = null;
            }



            // (Composing the condition)
            $this->where_field( $table_alias, $field )->like( $format[0] === '%' ? '%' : '', $value, $format[2] === '%' ? '%' : '' );

            if ( $i < $num_fields - 1 )
            {// (Index is not the last)
                // (Composing the condition)
                $this->or();
            }
        }



        // (Composing the condition)
        $this->where_raw( ' )' );



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function between (mixed $min, mixed $max)
    {
        // (Composing the query)
        $this->op('BETWEEN')->value($min)->and()->value($max);



        // Returning the value
        return $this;
    }



    # Returns [self]
    public function not ()
    {
        // Returning the value
        return $this->where_raw(' NOT ');
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



    # Returns [Query|Model]
    public function condition_end ()
    {
        // Returning the value
        return $this->query ?? $this->model;
    }



    # Returns [self]
    public function fill (array $values)
    {
        foreach ( $values as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $this->value = str_replace( ":$k", $this->connection->normalize_value( $v ), $this->value );
        }



        // Returning the value
        return $this;
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return $this->value === '' ? '1' : $this->value;
    }
}



?>
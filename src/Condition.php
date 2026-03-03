<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Query;
use \Solenoid\MySQL\Model;



class Condition extends Code
{
    private string     $current_op;



    public Connection  $connection;
    public Query       $query;
    public Model       $model;



    private function get_placeholder () : string
    {
        // Returning the value
        return 'cond_val_' . ( count( $this->values ) + 1 );
    }



    public function set_connection (Connection $connection) : self
    {
        // (Getting the value)
        $this->connection = $connection;



        // Returning the value
        return $this;
    }

    public function set_query (Query $query) : self
    {
        // (Getting the value)
        $this->query = $query;



        // Returning the value
        return $this;
    }

    public function set_model (Model $model) : self
    {
        // (Getting the value)
        $this->model = $model;



        // Returning the value
        return $this;
    }



    public function where_raw (string $content) : self
    {
        // (Appending the value)
        $this->sql .= $content;



        // Returning the value
        return $this;
    }



    public function where_expr (string $value, bool $raw = false) : self
    {
        if ( !$raw )
        {// (Value is not raw)
            // (Getting the value)
            $placeholder = $this->get_placeholder();



            // (Getting the value)
            $this->values[ $placeholder ] = $value;
        }



        // (Appending the value)
        $this->where_raw( $raw ? $value : ":$placeholder" );



        // Returning the value
        return $this;
    }

    public function where_field (string $column, ?string $table_alias = null) : self
    {
        // (Appending the value)
        $this->where_raw( ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column) ) . '`' );



        // Returning the value
        return $this;
    }

    public function where_tuple (array $fields, string $operator, array $values) : self
    {
        // (Setting the value)
        $num_fields = count( $fields );



        // (Composing the condition)
        $this->where_raw( '(' );

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
            $this->where_field( $field, $table_alias );

            if ( $i < $num_fields - 1 )
            {// (Index is not the last)
                // (Composing the condition)
                $this->where_raw( ', ' );
            }
        }

        // (Composing the condition)
        $this->where_raw( ')' );



        // (Composing the condition)
        $this->where_raw( " $operator " );



        // (Getting the value)
        $num_values = count( $values );



        // (Composing the condition)
        $this->where_raw( '(' );

        foreach ( $values as $i => $value )
        {// Processing each entry
            // (Getting the value)
            $placeholder = $this->get_placeholder();



            // (Getting the value)
            $this->values[ $placeholder ] = $value;



            // (Composing the condition)
            $this->where_raw( ":$placeholder" );

            if ( $i < $num_values - 1 )
            {// (Index is not the last)
                // (Composing the condition)
                $this->where_raw( ', ' );
            }
        }

        // (Composing the condition)
        $this->where_raw( ')' );



        // Returning the value
        return $this;
    }

    public function where () : self
    {
        // (Getting the values)
        $args     = func_get_args();
        $num_args = count( $args );



        // (Composing the condition)
        $this->where_raw( '(' );



        switch ( $num_args )
        {
            case 3:// (Format = COV)
                // (Getting the values)
                [ $column, $operator, $value ] = $args;

                // (Composing the condition)
                $this->where_field( $column )->op( $operator )->value( $value );
            break;

            case 2:// (Format = CV)
                // (Getting the value)
                [ $column, $value ] = $args;

                // (Composing the condition)
                $this->where_field( $column )->op( '=' )->value( $value );
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
                                $this->where_field( $column )->op( $operator )->value( $value );
                            break;

                            case 2:// (Format = CV)
                                // (Getting the values)
                                [ $column, $value ] = $expr;

                                // (Composing the condition)
                                $this->where_field( $column )->op( '=' )->value( $value );
                            break;

                            case 1:// (Format = RAW)
                                // (Composing the condition)
                                $this->where_raw( $expr[0] );
                            break;
                        }



                        if ( $i < $num_args - 1 )
                        {// (Index is not the last)
                            // (Composing the condition)
                            $this->and();
                        }
                    }
                }
                else
                {// (Value is not an array)
                    // (Composing the condition)
                    $this->where_raw( $args[0] );
                }
            break;
        }



        // (Composing the condition)
        $this->where_raw( ')' );



        // Returning the value
        return $this;
    }

    public function op (string $operator) : self
    {
        // (Getting the value)
        $this->current_op = $operator;



        // Returning the value
        return $this;
    }

    public function value (mixed $content, bool $raw = false) : self
    {
        if ( !$raw )
        {// (Value is not raw)
            if ( is_array( $content ) )
            {// Value is an array
                // (Setting the value)
                $list = '(';

                foreach ( $content as $i => $entry )
                {// Processing each entry
                    // (Getting the value)
                    $placeholder = $this->get_placeholder();



                    // (Appending the value)
                    $list .= ":$placeholder";

                    if ( $i < count( $content ) - 1 )
                    {// (Index is not the last)
                        // (Appending the value)
                        $list .= ', ';
                    }



                    // (Getting the value)
                    $this->values[ $placeholder ] = $entry;
                }

                // (Appending the value)
                $list .= ')';
            }
            else
            {// (Value is not an array)
                if ( $content === null )
                {// Match OK
                    if ( $this->current_op === '=' )
                    {// Match OK
                        // (Setting the value)
                        $this->current_op = 'IS';
                    }
                }



                // (Getting the value)
                $placeholder = $this->get_placeholder();



                // (Getting the value)
                $this->values[ $placeholder ] = $content;
            }
        }



        // (Appending the value)
        $this->where_raw( ' ' . $this->current_op . ' ' );

        // (Setting the value)
        $this->current_op = '';



        // (Appending the value)
        $this->where_raw( $list ?? ( $placeholder ? ":$placeholder" : $content ) );



        // Returning the value
        return $this;
    }



    public function filter (array $value) : self
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
                $this->where_field( $kk  )->op( '=' )->value( $vv );

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



    public function in (array $values, bool $raw = false) : self
    {
        if ( !$raw )
        {// (Values are not raw)
            // (Setting the value)
            $placeholders = [];

            foreach ( $values as $value )
            {// Processing each entry
                // (Getting the value)
                $placeholder = $this->get_placeholder();

                // (Appending the value)
                $placeholders[] = ":$placeholder";



                // (Getting the value)
                $this->values[ $placeholder ] = $value;
            }
        }


        
        // (Appending the value)
        $this->where_raw( ' IN ( ' . implode( ', ', $raw ? $values : $placeholders ) . ' )' );



        // Returning the value
        return $this;
    }



    public function is (mixed $value) : self
    {
        // (Composing the condition)
        $this->op( 'IS' )->value( $value );



        // Returning the value
        return $this;
    }

    public function is_not (mixed $value) : self
    {
        // (Composing the condition)
        $this->op( 'IS NOT' )->value( $value );



        // Returning the value
        return $this;
    }



    public function equal (mixed $value) : self
    {
        // (Composing the condition)
        $this->op( '=' )->value( $value );



        // Returning the value
        return $this;
    }

    public function not_equal (mixed $value) : self
    {
        // (Composing the condition)
        $this->op( '<>' )->value( $value );



        // Returning the value
        return $this;
    }



    public function lt (mixed $value) : self
    {
        // (Composing the condition)
        $this->op( '<' )->value( $value );



        // Returning the value
        return $this;
    }

    public function gt (mixed $value) : self
    {
        // (Composing the condition)
        $this->op( '>' )->value( $value );



        // Returning the value
        return $this;
    }



    public function like (string $start_wildcard = '%', string $value = '', string $end_wildcard = '%') : self
    {
        // (Composing the condition)
        $this->op( 'LIKE' )->value( "'" . $start_wildcard . $this->connection->sanitize_text( $value ) . $end_wildcard . "'", true );



        // Returning the value
        return $this;
    }



    public function filter_global (string $value, array $fields, string $format = '%V%') : self
    {
        // (Getting the value)
        $fields = array_unique( $fields );



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
            $this->where_field( $field, $table_alias )->like( $format[0] === '%' ? '%' : '', $value, $format[2] === '%' ? '%' : '' );

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

    public function filter_local (array $values, string $format = '%V%') : self
    {
        // (Getting the value)
        $num_fields = count( array_keys( $values ) );



        // (Composing the condition)
        $this->where_raw( '( ' );



        // (Setting the value)
        $i = -1;

        foreach ( $values as $field => $value )
        {// Processing each entry
            // (Incrementing the value)
            $i += 1;



            // (Composing the condition)
            $this->where_field( $field )->like( $format[0] === '%' ? '%' : '', $value, $format[2] === '%' ? '%' : '' );

            if ( $i < $num_fields - 1 )
            {// (Index is not the last)
                // (Composing the condition)
                $this->and();
            }
        }



        // (Composing the condition)
        $this->where_raw( ' )' );



        // Returning the value
        return $this;
    }



    public function between (mixed $min, mixed $max) : self
    {
        // (Composing the condition)
        $this->op( 'BETWEEN' )->value( $min )->and()->value( $max );



        // Returning the value
        return $this;
    }



    public function not () : self
    {
        // Returning the value
        return $this->where_raw( ' NOT ' );
    }

    public function and () : self
    {
        // Returning the value
        return $this->where_raw( ' AND ' );
    }

    public function or () : self
    {
        // Returning the value
        return $this->where_raw( ' OR ' );
    }



    public function condition_end () : Query|Model
    {
        // Returning the value
        return $this->query ?? $this->model;
    }



    public function fill (array $values) : self
    {
        foreach ( $values as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $this->values[ $k ] = $v;
        }



        // Returning the value
        return $this;
    }



    public function last_lop () : string|null
    {
        if ( $this->sql === '' )
        {// Value is empty
            // Returning the value
            return null;
        }



        // (Getting the value)
        $lop = trim( substr( $this->sql, -5 ) );



        // Returning the value
        return in_array( $lop, [ 'AND', 'OR', 'NOT' ] ) ? $lop : null;
    }



    public function __toString () : string
    {
        // Returning the value
        return $this->sql === '' ? '1' : $this->sql;
    }
}



?>
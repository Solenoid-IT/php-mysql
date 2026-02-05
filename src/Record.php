<?php



namespace Solenoid\MySQL;



class Record extends \stdClass
{
    public \stdClass $values;
    public \stdClass $relations;



    public function __construct (array &$value)
    {
        // (Getting the values)
        $this->values    = new \stdClass();
        $this->relations = new \stdClass();



        // (Getting the value)
        $object = json_decode( json_encode( $value ) );

        foreach ( $object as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $this->values->{ $k } = $v;
        }
    }



    public function get (string $column, $default = null) : mixed
    {
        if ( !str_contains( $column, '.' ) )
        {// Match failed
            // Returning the value
            return $this->values->{ $column } ?? $default;
        }



        // (Getting the value)
        $parts = explode( '.', $column );



        // (Getting the value)
        $current = $this->values;



        foreach ( $parts as $part )
        {// Processing each entry
            if ( is_object( $current ) && isset( $current->{ $part } ) )
            {// Match OK
                // (Getting the value)
                $current = $current->{ $part };
            }
            else
            {// Match failed
                // Returning the value
                return $default;
            }
        }



        // Returning the value
        return $current;
    }

    public function set_relation (string $name, array $value) : self
    {
        // (Getting the value)
        $this->relations->{ $name } = $value;



        // Returning the value
        return $this;
    }



    public function hash (string $algo = 'sha512') : string
    {
        // Returning the value
        return hash( $algo, json_encode( $this->values ) );
    }



    public function to_array () : array
    {
        // Returning the value
        return json_decode( json_encode( $this->values ), true );
    }



    public function iterate (mixed $target = null, string $prefix = '') : \Generator
    {
        // (Getting the value)
        $current = $target ?? $this->values;



        // (Getting the value)
        $keys = array_keys( (array) $current );



        foreach ( $keys as $key ) 
        {// Processing each entry
            // (Getting the values)
            $full_key = $prefix === '' ? (string) $key : "$prefix.$key";
            $value    = $current->$key;

            if ( is_object( $value ) || is_array( $value ) ) 
            {// (Node found)
                // (Yielding the value)
                yield from $this->iterate( $value, $full_key );



                if ( is_object( $value ) && count( get_object_vars( $value ) ) === 0 )
                {// Match OK
                    // (Unsetting the element)
                    unset( $current->$key );
                }
                else
                if ( is_array( $value ) && count( $value ) === 0 )
                {// Match OK
                    // (Removing the element)
                    unset( $current->$key );
                }
            } 
            else 
            {// (Leaf found)
                // (Yielding the result pair)
                yield [ $full_key, $value ];

                // (Removing the element)
                unset( $current->$key );
            }
        }
    }



    public function __get (string $key)
    {
        // Returning the value
        return $this->get( $key );
    }

    public function __unset (string $key)
    {
        if ( isset( $this->values->{$key} ) )
        {// Value found
            // (Unsetting the value)
            unset( $this->values->{$key} );
        }
    }
}



?>
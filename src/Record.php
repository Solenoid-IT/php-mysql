<?php



namespace Solenoid\MySQL;



class Record extends \stdClass
{
    public function __construct (array &$value)
    {
        // (Getting the value)
        $object = json_decode( json_encode($value) );

        foreach ( $object as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $this->{ $k } = $v;
        }
    }



    public function get (string $column, $default = null) : mixed
    {
        if ( !str_contains( $column, '.' ) )
        {// Match failed
            // Returning the value
            return $this->{ $column } ?? $default;
        }



        // (Getting the value)
        $parts = explode( '.', $column );



        // (Getting the value)
        $current = $this;



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



    public function hash (string $alg = 'sha512') : string
    {
        // Returning the value
        return hash( $alg, implode( '', array_values( $this->to_array() ) ) );
    }



    public function to_array () : array
    {
        // Returning the value
        return json_decode( json_encode($this), true );
    }
}



?>
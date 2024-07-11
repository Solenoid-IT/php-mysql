<?php



namespace Solenoid\MySQL;



class Record
{
    # Returns [self]
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



    # Returns [string]
    public function hash (string $alg = 'sha512')
    {
        // Returning the value
        return hash( $alg, implode( '', array_values( $this->to_array() ) ) );
    }



    # Returns [assoc]
    public function to_array ()
    {
        // Returning the value
        return json_decode( json_encode($this), true );
    }
}



?>
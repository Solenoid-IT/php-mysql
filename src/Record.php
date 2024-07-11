<?php



namespace Solenoid\MySQL;



class Record
{
    private array $value;



    # Returns [self]
    public function __construct (array &$value)
    {
        // (Getting the value)
        $this->value = &$value;
    }



    # Returns [string]
    public function hash (string $alg = 'sha512')
    {
        // Returning the value
        return hash( $alg, implode( '', array_values( $this->value ) ) );
    }



    # Returns [assoc]
    public function & to_array ()
    {
        // Returning the value
        return $this->value;
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return json_encode( $this->value );
    }
}



?>
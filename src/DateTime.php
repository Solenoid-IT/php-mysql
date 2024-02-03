<?php



namespace Solenoid\MySQL;



class DateTime
{
    public string $value;



    # Returns [void]
    public function __construct (string $value)
    {
        // (Getting the value)
        $this->value = $value;
    }

    # Returns [DateTime]
    public static function create (string $value)
    {
        // Returning the value
        return new DateTime( $value );
    }



    # Returns [string]
    public static function fetch (?int $timestamp = null, bool $utc = false)
    {
        // (Getting the value)
        $timestamp = $timestamp ?? time();



        // (Setting the value)
        $format = 'Y-m-d H:i:s';



        // Returning the value
        return $utc ? gmdate( $format, $timestamp ) : date( $format, $timestamp );
    }



    # Returns [string]
    public function to_iso ()
    {
        // Returning the value
        return str_replace( ' ', 'T', $this->value ) . '.000Z';
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return $this->value;
    }
}



?>
<?php



namespace Solenoid\MySQL;



class Record
{
    private array $kv_data;



    # Returns [self]
    public function __construct (array $kv_data)
    {
        // (Getting the value)
        $this->kv_data = $kv_data;
    }

    # Returns [Record]
    public static function create (array $kv_data)
    {
        // Returning the value
        return new Record( $kv_data );
    }



    # Returns [string]
    public function hash (string $alg = 'sha512')
    {
        // Returning the value
        return hash( $alg, implode( '', array_values( $this->kv_data ) ) );
    }
}



?>
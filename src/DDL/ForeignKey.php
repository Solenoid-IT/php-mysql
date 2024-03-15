<?php



namespace Solenoid\MySQL\DDL;



use \Solenoid\MySQL\DDL\Reference;



class ForeignKey
{
    public array     $key;
    public Reference $reference;

    public array     $rules;



    # Returns [self]
    public function __construct (array $key, Reference $reference, array $rules = [])
    {
        // (Getting the values)
        $this->key       = $key;
        $this->reference = $reference;

        $this->rules     = $rules;
    }

    # Returns [ForeignKey]
    public static function create (array $key, Reference $reference, array $rules = [])
    {
        // Returning the value
        return new ForeignKey( $key, $reference, $rules );
    }



    # Returns [string]
    public function __toString ()
    {
        // (Getting the values)
        $key   = implode( ',', array_map( function ($k) { return "`$k`"; }, $this->key ) );
        $rules = implode( "\n", $this->rules );



        // Returning the value
        return
            <<<EOD
            FOREIGN KEY ($key)
            $this->reference
            EOD
                .
            $rules ? "\n" . $rules : ''
        ;
    }
}



?>
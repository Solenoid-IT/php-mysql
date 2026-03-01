<?php



namespace Solenoid\MySQL;



class Command
{
    public function __construct (public readonly string $sql, public readonly array $values = []) {}



    public function simulate () : string
    {
        // Returning the value
        return 'ahcid';
    }
}



?>
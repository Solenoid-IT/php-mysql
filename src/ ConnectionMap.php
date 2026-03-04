<?php



namespace Solenoid\MySQL;



class ConnectionMap
{
    private array $values = [];



    public function get (string $name) : Connection|null
    {
        // (Getting the value)
        return $this->values[ $name ] ?? null;
    }

    public function set (string $name, Connection $connection) : self
    {
        // (Getting the value)
        $this->values[ $name ] = $connection;



        // Returning the value
        return $this;
    }
}



?>
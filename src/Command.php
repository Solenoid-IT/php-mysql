<?php



namespace Solenoid\MySQL;



class Command extends Code
{
    private Connection $connection;



    public function set_connection (Connection $connection) : self
    {
        // (Getting the value)
        $this->connection = $connection;



        // Returning the value
        return $this;
    }
}



?>
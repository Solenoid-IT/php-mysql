<?php



namespace Solenoid\MySQL;



class Command
{
    private Connection $connection;



    public function __construct (public string $sql, public array $values = []) {}



    public function set_connection (Connection $connection) : self
    {
        // (Getting the value)
        $this->connection = $connection;



        // Returning the value
        return $this;
    }



    public function simulate () : string
    {
        // (Getting the value)
        $sql = $this->sql;

        foreach ( $this->values as $k => $v )
        {// Processing each entry
            // (Normalizing the value)
            $nv = $this->connection->normalize_value( $v );

            if ( $nv === false )
            {// (Unable to normalize the value)
                // Throwing an exception
                throw new \Exception( 'Unable to normalize the value' );
            }



            // (Getting the value)
            $sql = str_replace( "{! $k !}", $v, $sql );
            $sql = str_replace( "{{ $k }}", $nv, $sql );
            $sql = str_replace( ":$k", $nv, $sql );
        }



        // Returning the value
        return $sql;
    }
}



?>
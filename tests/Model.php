<?php



use \Solenoid\MySQL\Model;
use \Solenoid\MySQL\ConnectionStore;



class User extends Model
{
    private static self $instance;



    public string $conn_id  = 'local';
    public string $database = 'db';
    public string $table    = 'user';



    # Returns [self]
    private function __construct ()
    {
        // (Getting the value)
        $connection = ConnectionStore::get( $this->conn_id );

        if ( !$connection )
        {// Value not found
            // (Getting the value)
            $message = "Connection '" . $this->conn_id . "' not found";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return;
        }



        // Calling the function
        parent::__construct( $connection, $this->database, $this->table );
    }

    # Returns [self]
    public static function fetch ()
    {
        if ( !isset( self::$instance ) )
        {// Value not found
            // (Getting the value)
            self::$instance = new self();
        }



        // (Resetting the model)
        self::$instance->reset();



        // Returning the value
        return self::$instance;
    }
}



echo json_encode( User::fetch()->where( 'age', '<=', 30 )->order( [ 'age' => SORT_ASC ] )->list(), JSON_PRETTY_PRINT );



?>
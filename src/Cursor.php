<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\Vector\Vector;



class Cursor
{
    private \mysqli_result    $mysqli_result;

    private ?array                   $schema;
    private ?string        $column_separator;

    private string                     $mode;

    private Connection           $connection;



    # Returns [self]
    public function __construct (\mysqli_result $mysqli_result, ?array $schema = null, ?string $column_separator = null)
    {
        // (Getting the values)
        $this->mysqli_result    = $mysqli_result;

        $this->schema           = $schema;
        $this->column_separator = $column_separator;



        // (Setting the mode)
        $this->set_mode( 'record' );
    }

    # Returns [Cursor]
    public static function create (\mysqli_result $mysqli_result, ?array $schema = null, ?string $column_separator = null)
    {
        // Returning the value
        return new Cursor( $mysqli_result, $schema, $column_separator );
    }



    # Returns [self]
    public function set_connection (Connection &$connection)
    {
        // (Getting the value)
        $this->connection = &$connection;



        // Returning the value
        return $this;
    }



    # Returns [int]
    public function count ()
    {
        // Returning the value
        return mysqli_num_rows( $this->mysqli_result );
    }



    # Returns [bool]
    public function is_empty ()
    {
        // Returning the value
        return $this->count() === 0;
    }



    # Returns [self|false] | Throws [Exception]
    public function set_mode (string $mode)
    {
        switch ( $mode )
        {
            case 'record':
            case 'value':
                // OK
            break;

            default:
                // (Setting the value)
                $message = "Mode '$mode' is not a valid option";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
        }



        // (Getting the value)
        $this->mode = $mode;



        // Returning the value
        return $this;
    }



    # Returns [assoc|false|null] | Throws [Exception]
    public function fetch_record ()
    {
        // (Fetching the assoc)
        $record = mysqli_fetch_assoc( $this->mysqli_result );

        if ( $record === false )
        {// (Unable to fetch the record)
            // (Setting the value)
            $message = "Unable to fetch the record";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return null;
        }

        if ( $record === null )
        {// (There are no more records)
            // Returning the value
            return false;
        }



        if ( $this->schema )
        {// Value found
            foreach ($record as $k => $v)
            {// Processing each entry
                if ( $v === 'null' && $this->schema[ $k ]['null'] )
                {// Match OK
                    // (Setting the value)
                    $v = null;
                }
                else
                {// Match failed
                    switch ( $this->schema[ $k ]['type'] )
                    {
                        case 'int':
                            // (Getting the value)
                            $v = (int) $v;
                        break;

                        case 'float':
                            // (Getting the value)
                            $v = (float) $v;
                        break;

                        case 'datetime':
                            // (Getting the value)
                            $timezone = $this->connection->get_timezone_hms( 2 );
                            $timezone = $timezone === '+00:00' ? 'Z' : $timezone;



                            // (Getting the value)
                            $v = str_replace( ' ', 'T', $v ) . $timezone;
                        break;

                        default:
                            // (Getting the value)
                            $v = $v;
                    }
                }



                // (Getting the value)
                $record[ $k ] = $v;
            }
        }

        if ( $this->column_separator )
        {// Value found
            // (Getting the value)
            $record = Vector::create( $record, $this->column_separator )->expand()->to_array();
        }



        // Returning the value
        return $record;
    }

    # Returns [string|false|null] | Throws [Exception]
    public function fetch_value ()
    {
        // (Fetching the record)
        $record = $this->fetch_record();

        if ( $record === null )
        {// (Unable to fetch the record)
            // (Setting the value)
            $message = "Unable to fetch the record";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return null;
        }

        if ( $record === false )
        {// (There are no more records)
            // Returning the value
            return false;
        }



        // Returning the value
        return ( array_values( $record ) )[0];
    }



    # Returns [bool]
    public function reset ()
    {
        // Returning the value
        return mysqli_data_seek( $this->mysqli_result, 0 );
    }

    # Returns [void]
    public function walk (callable $handle_entry)
    {
        while ( $record = $this->fetch_record() )
        {// Processing each entry
            switch ( $this->mode )
            {
                case 'record':
                    // (Calling the function)
                    $break = $handle_entry( $record ) === false;
                break;

                case 'value':
                    // (Calling the function)
                    $break = $handle_entry( ( array_values( $record ) )[0] ) === false;
                break;
            }



            if ( $break )
            {// Breaking the iteration
                break;
            }
        }
    }

    # Returns [array<(assoc|string)>] | Throws [Exception]
    public function to_array (?callable $transform_entry = null)
    {
        // (Setting the value)
        $values = [];

        while ( $record = $this->fetch_record() )
        {// Processing each entry
            if ( $transform_entry === null )
            {// Value not found
                switch ( $this->mode )
                {
                    case 'record':
                        // (Getting the value)
                        $record = $record;
                    break;

                    case 'value':
                        // (Getting the value)
                        $value = ( array_values( $record ) )[0];
                    break;
                }
            }
            else
            {// Value found
                switch ( $this->mode )
                {
                    case 'record':
                        // (Getting the value)
                        $record = $transform_entry( $record );
                    break;

                    case 'value':
                        // (Getting the value)
                        $value = ( array_values( $record ) )[0];

                        // (Getting the value)
                        $value = $transform_entry( $value );
                    break;
                }
            }



            switch ( $this->mode )
            {
                case 'record':
                    // (Appending the value)
                    $values[] = $record;
                break;

                case 'value':
                    // (Appending the value)
                    $values[] = $value;
                break;
            }
        }



        // Returning the value
        return $values;
    }



    # Returns [(assoc|string)|false|null] | Throws [Exception]
    public function fetch_head ()
    {
        // (Setting the value)
        $head = null;

        while ( $record = $this->fetch_record() )
        {// Processing each entry
            switch ( $this->mode )
            {
                case 'record':
                    // (Getting the value)
                    $head = $record;
                break;

                case 'value':
                    // (Getting the value)
                    $head = ( array_values( $record ) )[0];
                break;
            }



            // Breaking the iteration
            break;
        }

        if ( $head === null )
        {// (There are no more records to fetch)
            // Returning the value
            return false;
        }



        if ( !$this->reset() )
        {// (Unable to reset the cursor)
            // (Setting the value)
            $message = "Unable to reset the cursor";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return null;
        }



        // Returning the value
        return $head;
    }

    # Returns [(assoc|string)|false|null] | Throws [Exception]
    public function fetch_tail ()
    {
        // (Setting the value)
        $tail = null;

        while ( $record = $this->fetch_record() )
        {// Processing each entry
            switch ( $this->mode )
            {
                case 'record':
                    // (Getting the value)
                    $tail = $record;
                break;
                
                case 'value':
                    // (Getting the value)
                    $tail = ( array_values( $record ) )[0];
                break;
            }
            
        }

        if ( $tail === null )
        {// (There are no more records to fetch)
            // Returning the value
            return false;
        }



        if ( !$this->reset() )
        {// (Unable to reset the cursor)
            // (Setting the value)
            $message = "Unable to reset the cursor";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return null;
        }



        // Returning the value
        return $tail;
    }



    # Returns [array<string>|null] | Throws [Exception]
    public function fetch_schema ()
    {
        // (Fetching the record)
        $record = $this->fetch_record();

        if ( $record === null )
        {// (Unable to fetch the record)
            // (Setting the value)
            $message = "Unable to fetch the record";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return null;
        }

        if ( $record === false )
        {// (Cursor has been closed)
            // (Setting the value)
            $message = "Cursor has been closed";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return null;
        }



        // (Getting the value)
        $schema = array_keys( $record );



        if ( !$this->reset() )
        {// (Unable to reset the cursor)
            // (Setting the value)
            $message = "Unable to reset the cursor";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return null;
        }



        // Returning the value
        return $schema;
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return json_encode( $this->to_array() );
    }
}



?>
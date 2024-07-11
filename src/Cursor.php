<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Record;

use \Solenoid\Vector\Vector;



class Cursor
{
    const TYPES =
    [
        MYSQLI_TYPE_DECIMAL =>
        [
            'type' => 'DECIMAL',
            'cast' => 'float'
        ],

        MYSQLI_TYPE_NEWDECIMAL =>
        [
            'type' => 'DECIMAL',
            'cast' => 'float'
        ],

        MYSQLI_TYPE_BIT =>
        [
            'type' => 'BIT',
            'cast' => 'bool'
        ],

        MYSQLI_TYPE_TINY =>
        [
            'type' => 'TINYINT',
            'cast' => 'int'
        ],

        MYSQLI_TYPE_SHORT =>
        [
            'type' => 'SMALLINT',
            'cast' => 'int'
        ],

        MYSQLI_TYPE_LONG =>
        [
            'type' => 'INT',
            'cast' => 'int'
        ],

        MYSQLI_TYPE_FLOAT =>
        [
            'type' => 'FLOAT',
            'cast' => 'float'
        ],

        MYSQLI_TYPE_DOUBLE =>
        [
            'type' => 'DOUBLE',
            'cast' => 'float'
        ],

        MYSQLI_TYPE_TIMESTAMP =>
        [
            'type' => 'TIMESTAMP',
            'cast' => 'string:iso-8601'
        ],

        MYSQLI_TYPE_LONGLONG =>
        [
            'type' => 'BIGINT',
            'cast' => 'int'
        ],

        MYSQLI_TYPE_INT24 =>
        [
            'type' => 'MEDIUMINT',
            'cast' => 'int'
        ],

        MYSQLI_TYPE_DATE =>
        [
            'type' => 'DATE',
            'cast' => 'string:iso-8601'
        ],

        MYSQLI_TYPE_TIME =>
        [
            'type' => 'TIME',
            'cast' => 'time'
        ],

        MYSQLI_TYPE_DATETIME =>
        [
            'type' => 'DATETIME',
            'cast' => 'string:iso-8601'
        ],

        MYSQLI_TYPE_YEAR =>
        [
            'type' => 'YEAR',
            'cast' => 'int'
        ],

        MYSQLI_TYPE_NEWDATE =>
        [
            'type' => 'DATE',
            'cast' => 'string:iso-8601'
        ],

        MYSQLI_TYPE_INTERVAL =>
        [
            'type' => 'INTERVAL',
            'cast' => 'undefined'
        ],

        MYSQLI_TYPE_ENUM =>
        [
            'type' => 'ENUM',
            'cast' => 'undefined'
        ],

        MYSQLI_TYPE_SET =>
        [
            'type' => 'SET',
            'cast' => 'undefined'
        ],

        MYSQLI_TYPE_TINY_BLOB =>
        [
            'type' => 'TINYBLOB',
            'cast' => 'string'
        ],

        MYSQLI_TYPE_MEDIUM_BLOB =>
        [
            'type' => 'MEDIUMBLOB',
            'cast' => 'string'
        ],

        MYSQLI_TYPE_LONG_BLOB =>
        [
            'type' => 'LONGBLOB',
            'cast' => 'string'
        ],

        MYSQLI_TYPE_BLOB =>
        [
            'type' => 'BLOB',
            'cast' => 'string'
        ],

        MYSQLI_TYPE_VAR_STRING =>
        [
            'type' => 'VARCHAR',
            'cast' => 'string'
        ],

        MYSQLI_TYPE_STRING =>
        [
            'type' => 'CHAR',
            'cast' => 'string'
        ],

        MYSQLI_TYPE_CHAR =>
        [
            'type' => 'TINYINT',
            'cast' => 'int'
        ],

        MYSQLI_TYPE_GEOMETRY =>
        [
            'type' => 'GEOMETRY',
            'cast' => 'undefined'
        ],

        MYSQLI_TYPE_JSON =>
        [
            'type' => 'JSON',
            'cast' => 'string:json'
        ],

        MYSQLI_ENUM_FLAG =>
        [
            'type' => 'ENUM',
            'cast' => 'undefined'
        ],

        MYSQLI_BINARY_FLAG =>
        [
            'type' => 'BINARY',
            'cast' => 'string'
        ]
    ]
    ;



    private \mysqli_result    $mysqli_result;

    private string                     $mode;

    private Connection           $connection;

    private bool               $typed_fields;



    # Returns [self]
    public function __construct (\mysqli_result $mysqli_result)
    {
        // (Getting the value)
        $this->mysqli_result = $mysqli_result;



        // (Setting the value)
        $this->typed_fields = false;



        // (Setting the mode)
        $this->set_mode( 'record' );
    }



    # Returns [self]
    public function set_connection (Connection &$connection)
    {
        // (Getting the value)
        $this->connection = &$connection;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function set_typed_fields (bool $value)
    {
        // (Getting the value)
        $this->typed_fields = $value;



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



    # Returns [Record|false|null] | Throws [Exception]
    public function fetch_record (?callable $transform = null)
    {
        if ( $transform === null ) $transform = function ($record) { return $record; };



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



        if ( $this->typed_fields )
        {// Value is true
            // (Setting the value)
            $types = [];



            // (Getting the value)
            $fields = mysqli_fetch_fields( $this->mysqli_result );

            foreach ( $fields as $field )
            {// Processing each entry
                // (Getting the value)
                $types[ $field->name ] = self::TYPES[ $field->type ] ?? self::TYPES[ MYSQLI_TYPE_BLOB ];
            }



            foreach ( $record as $k => $v )
            {// Processing each entry
                if ( $v === null ) continue;

                switch ( $types[ $k ]['cast'] )
                {
                    case 'int':
                        // (Getting the value)
                        $record[ $k ] = (int) $v;
                    break;

                    case 'float':
                        // (Getting the value)
                        $record[ $k ] = (float) $v;
                    break;

                    case 'bool':
                        // (Getting the value)
                        $record[ $k ] = $v === '1';
                    break;

                    case 'string:iso-8601':
                        // (Getting the value)
                        $timezone = $this->connection->get_timezone_hms( 2 );
                        $timezone = $timezone === '+00:00' ? 'Z' : $timezone;



                        // (Getting the value)
                        $record[ $k ] = str_replace( ' ', 'T', $v ) . $timezone;
                    break;

                    case 'string:json':
                        // (Getting the value)
                        $record[ $k ] = json_decode( $v, true );
                    break;

                    default:
                        // (Doing nothing)
                }
            }
        }



        // (Getting the value)
        $record = Vector::create( $record, '.' )->expand()->to_array();



        // Returning the value
        return $transform( new Record($record) );
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
        return ( array_values( $record->to_array() ) )[0];
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
                    $break = $handle_entry( ( array_values( $record->to_array() ) )[0] ) === false;
                break;
            }



            if ( $break )
            {// Breaking the iteration
                break;
            }
        }
    }



    # Returns [array<(Record|string)>] | Throws [Exception]
    public function fetch_all (?callable $transform_entry = null)
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
                        $value = ( array_values( $record->to_array() ) )[0];
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
                        $value = ( array_values( $record->to_array() ) )[0];

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



    # Returns [(Record|string)|false|null] | Throws [Exception]
    public function fetch_head (?callable $transform = null)
    {
        // (Setting the value)
        $head = null;

        while ( $record = $this->fetch_record($transform) )
        {// Processing each entry
            switch ( $this->mode )
            {
                case 'record':
                    // (Getting the value)
                    $head = $record;
                break;

                case 'value':
                    // (Getting the value)
                    $head = ( array_values( $record->to_array() ) )[0];
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

    # Returns [(Record|string)|false|null] | Throws [Exception]
    public function fetch_tail (?callable $transform = null)
    {
        // (Setting the value)
        $tail = null;

        while ( $record = $this->fetch_record($transform) )
        {// Processing each entry
            switch ( $this->mode )
            {
                case 'record':
                    // (Getting the value)
                    $tail = $record;
                break;
                
                case 'value':
                    // (Getting the value)
                    $tail = ( array_values( $record->to_array() ) )[0];
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
        return json_encode( $this->fetch_all() );
    }
}



?>
<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Model;
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
            #'cast' => 'string:iso-8601'
            'cast' => 'string'
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
            #'cast' => 'string:iso-8601'
            'cast' => 'string'
        ],

        MYSQLI_TYPE_TIME =>
        [
            'type' => 'TIME',
            'cast' => 'time'
        ],

        MYSQLI_TYPE_DATETIME =>
        [
            'type' => 'DATETIME',
            #'cast' => 'string:iso-8601'
            'cast' => 'string'
        ],

        MYSQLI_TYPE_YEAR =>
        [
            'type' => 'YEAR',
            'cast' => 'int'
        ],

        MYSQLI_TYPE_NEWDATE =>
        [
            'type' => 'DATE',
            #'cast' => 'string:iso-8601'
            'cast' => 'string'
        ],

        /* php8.4 bugfix

        MYSQLI_TYPE_INTERVAL =>
        [
            'type' => 'INTERVAL',
            'cast' => 'undefined'
        ],

        */

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



    protected string     $mode = 'record';
    protected Connection $connection;
    protected bool       $typed_fields = true;
    protected ?Model     $model = null;



    protected static array $cached_models = [];
    
    

    private function fetch_record (?callable $transform = null) : Record|null
    {
        if ( $transform === null ) $transform = function ($record) { return $record; };



        // (Getting the value)
        $record = mysqli_fetch_assoc( $this->mysqli_result );

        if ( $record === false )
        {// (Unable to fetch the record)
            // Throwing the exception
            throw new \Exception( 'Unable to fetch the record' );
        }



        if ( $record === null )
        {// (There are no more records)
            // Returning the value
            return null;
        }



        if ( $this->typed_fields )
        {// Value is true
            // (Setting the value)
            $types = [];



            // (Getting the value)
            $model_class = $this->model ? get_class( $this->model ) : null;

            if ( $model_class )
            {// Value found
                // (Setting the value)
                self::$cached_models[ $model_class ] = [];



                // (Getting the value)
                $type_cast = self::$cached_models[ $model_class ]['type_cast'] ?? TypeCast::find( $model_class );



                // (Getting the value)
                self::$cached_models[ $model_class ]['type_cast'] = $type_cast->fields;
            }



            // (Getting the value)
            $fields = self::$cached_models[ $model_class ]['fields'] ?? mysqli_fetch_fields( $this->mysqli_result );

            foreach ( $fields as $field )
            {// Processing each entry
                // (Getting the value)
                $types[ $field->name ] = self::TYPES[ $field->type ] ?? self::TYPES[ MYSQLI_TYPE_BLOB ];
            }



            if ( $model_class )
            {// Value found
                // (Getting the value)
                self::$cached_models[ $model_class ]['fields'] = $fields;
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



            foreach ( $record as $k => $v )
            {// Processing each entry
                // (Getting the value)
                $cast = self::$cached_models[ $model_class ]['type_cast'][ $k ] ?? null;

                if ( !$cast ) continue;



                switch ( $cast )
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
                }
            }
        }



        // (Getting the value)
        $record = Vector::create( $record, '.' )->expand()->to_array();



        // Returning the value
        return $transform( new Record( $record ) );
    }



    public function __construct (private \mysqli_result $mysqli_result) {}



    public function set_connection (Connection $connection) : static
    {
        // (Getting the value)
        $this->connection = $connection;



        // Returning the value
        return $this;
    }

    public function set_typed_fields (bool $value) : static
    {
        // (Getting the value)
        $this->typed_fields = $value;



        // Returning the value
        return $this;
    }

    public function set_model (Model $model) : static
    {
        // (Getting the value)
        $this->model = $model;



        // Returning the value
        return $this;
    }



    public function set_mode (string $mode) : static|false
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



    



    public function read () : Record|null
    {
        // (Getting the value)
        $result = $this->fetch_record();

        if ( $result === null )
        {// (Cursor is at the end)
            // (Closing the cursor)
            $this->close();
        }



        // Returning the value
        return $result;
    }

    public function close () : static
    {
        // (Freeing the result)
        $this->connection->free_result();



        // Returning the value
        return $this;
    }



    public function count () : int
    {
        // Returning the value
        return mysqli_num_rows( $this->mysqli_result );
    }

    public function is_empty () : bool
    {
        // Returning the value
        return $this->count() === 0;
    }



    public function head () : Record|null
    {
        // (Getting the value)
        $record = $this->read();



        // (Closing the cursor)
        $this->close();



        // Returning the value
        return $record;
    }

    public function value () : mixed
    {
        // (Getting the value)
        $values = ( new Vector( (array) $this->head()->values ) )->compress()->to_array();

        foreach ( $values as $k => $v )
        {// Processing each entry
            // Returning the value
            return $v;
        }



        // Returning the value
        return null;
    }



    /**
     * @return array<Record>
     */
    public function list () : array
    {
        // (Setting the value)
        $records = [];

        while ( $record = $this->read() )
        {// Processing each entry
            // (Appending the value)
            $records[] = $record;
        }



        // Returning the value
        return $records;
            
    }



    public function __toString () : string
    {
        // Returning the value
        return json_encode( $this->list() );
    }
}



?>
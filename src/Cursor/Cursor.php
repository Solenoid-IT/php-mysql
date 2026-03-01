<?php



namespace Solenoid\MySQL\Cursor;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Model;
use \Solenoid\MySQL\Record;

use \Solenoid\Vector\Vector;



abstract class Cursor
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



    protected static array $cached_fields = [];



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



    abstract public function read () : Record|null;

    abstract public function close () : static;



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
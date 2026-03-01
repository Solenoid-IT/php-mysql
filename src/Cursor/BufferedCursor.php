<?php



namespace Solenoid\MySQL\Cursor;



use \Solenoid\MySQL\Record;

use \Solenoid\Vector\Vector;



class BufferedCursor extends Cursor
{
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



            // (Getting the value)
            $fields = self::$cached_fields[ $model_class ] ?? mysqli_fetch_fields( $this->mysqli_result );

            foreach ( $fields as $field )
            {// Processing each entry
                // (Getting the value)
                $types[ $field->name ] = self::TYPES[ $field->type ] ?? self::TYPES[ MYSQLI_TYPE_BLOB ];
            }



            if ( $this->model )
            {// Value found
                // (Getting the value)
                self::$cached_fields[ $model_class ] = $fields;
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
        return $transform( new Record( $record ) );
    }



    public function __construct (private \mysqli_result $mysqli_result) {}



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
}



?>
<?php



namespace Solenoid\MySQL;



class Code
{
    public function __construct (public string $sql = '', public array $values = []) {}



    public static function quote (string $property) : string
    {
        // Returning the value
        return '`' . str_replace( '`', '', $property ) . '`';
    }

    public static function format (mixed $value) : string
    {
        switch ( gettype( $value ) )
        {
            case 'NULL':
                // (Setting the value)
                $value = 'NULL';
            break;

            case 'boolean':
                switch ( $value )
                {
                    case false:
                        // (Setting the value)
                        $value = 'FALSE';
                    break;

                    case true:
                        // (Setting the value)
                        $value = 'TRUE';
                    break;
                }
            break;

            case 'integer':
                // (Getting the value)
                $value = (string) $value;
            break;

            case 'float':
            case 'double':
                // (Getting the value)
                $value = (string) $value;
            break;

            case 'array':
                // (Encoding the value as JSON)
                $value = json_encode( $value );
                        
                if ( $value === false )
                {// (Unable to encode the value as JSON)
                    // Throwing an exception
                    throw new \Exception( 'Unable to encode the value as JSON' );
                }



                // (Replacing the value)
                $value = str_replace( '\'', '\\\'', $value );
                $value = str_replace( "\\\"", "\\\\\"", $value );
                $value = preg_replace( '/\\\\u([0-9a-f]{4})/', '\\\\\\\\u$1', $value );



                // (Getting the value)
                $value = "'$value'";
            break;

            case 'string':
            case 'object':
                // (Getting the value)
                $value = "'$value'";
            break;
        }



        // Returning the value
        return $value;
    }



    public function simulate () : string
    {
        // (Getting the value)
        $sql = $this->sql;

        foreach ( $this->values as $k => $v )
        {// Processing each entry
            // (Getting the value)
            $sql = str_replace( ":$k", self::format( $v ), $sql );
        }



        // Returning the value
        return $sql;
    }



    public function __toString () : string
    {
        // Returning the value
        return $this->sql;
    }
}



?>
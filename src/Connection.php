<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Entity;



class Connection
{
    private ?\mysqli $c;



    private ?string  $host;
    private ?int     $port;

    private ?string  $username;
    private ?string  $password;

    private ?string  $database;

    private ?string  $socket;

    private string   $charset;

    private          $debug;
    private          $queries;

    private          $mysqli_result;

    private string   $insert_mode;

    private array    $event_listeners;

    private string   $timezone_hms;

    private ?string  $column_separator;



    # Returns [self]
    public function __construct
    (
        ?string $host     = null,
        ?int    $port     = null,

        ?string $username = null,
        ?string $password = null,
        
        ?string $database = null,

        ?string $socket   = null
    )
    {
        // (Setting the value)
        $this->c = null;



        // (Getting the values)
        $this->host     = $host ?? ini_get("mysqli.default_host");
        $this->port     = $port ?? ini_get("mysqli.default_port");

        $this->username = $username ?? ini_get("mysqli.default_user");
        $this->password = $password ?? ini_get("mysqli.default_pw");

        $this->database = $database ?? '';

        $this->socket   = $socket ?? ini_get("mysqli.default_socket");



        // (Setting the values)
        $this->charset          = 'utf8';

        $this->debug            = false;
        $this->queries          = [];

        $this->insert_mode      = 'standard';

        $this->event_listeners  = [];

        $this->timezone_hms     = '';

        $this->column_separator = null;
    }



    # Returns [self]
    public function set_debug (bool $value)
    {
        // (Getting the value)
        $this->debug = $value;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function set_charset (string $value)
    {
        // (Getting the value)
        $this->charset = $value;



        // Returning the value
        return $this;
    }



    # Returns [self|false]
    public function open ()
    {
        // (Opening the connection)
        $this->c = mysqli_connect
        (
            $this->host,
            $this->username,
            $this->password,

            $this->database,
            
            $this->port,
            $this->socket
        )
        ;

        if ( $this->c === false )
        {// (Unable to open the connection)
            // Returning the value
            return false;
        }



        if ( !mysqli_set_charset( $this->c, $this->charset ) )
        {// (Unable to set the charset)
            // Returning the value
            return false;
        }



        if ( $this->execute( "SELECT TIMEDIFF( NOW(), UTC_TIMESTAMP ) AS 'timezone';" ) === false )
        {// (Unable to get the timezone HMS)
            // Returning the value
            return false;
        }

        // (Getting the value)
        $this->timezone_hms = $this->fetch_cursor()->set_mode('value')->fetch_head();
        $this->timezone_hms = $this->timezone_hms[0] === '-' ? $this->timezone_hms : '+' . $this->timezone_hms;



        // (Triggering the event)
        $this->trigger_event
        (
            'open',
            [
                'connection' => $this
            ]
        )
        ;



        // Returning the value
        return $this;
    }

    # Returns [self|false]
    public function close ()
    {
        if ( !mysqli_close( $this->c ) )
        {// (Unable to close the connection)
            // Returning the value
            return false;
        }



        // (Setting the value)
        $this->c = null;



        // (Triggering the event)
        $this->trigger_event
        (
            'close',
            [
                'connection' => $this
            ]
        )
        ;



        // Returning the value
        return $this;
    }



    # Returns [int]
    public function get_error_code ()
    {
        // Returning the value
        return mysqli_errno( $this->c );
    }
    
    # Returns [string]
    public function get_error_msg ()
    {
        // Returning the value
        return mysqli_error( $this->c );
    }

    # Returns [assoc]
    public function get_errors ()
    {
        // Returning the value
        return
        [
            'connection'        =>
            [
                'error_code'    => mysqli_connect_errno(),
                'error_message' => mysqli_connect_error()
            ],

            'execution'         =>
            [
                'error_code'    => $this->c ? mysqli_errno( $this->c ) : 0,
                'error_message' => $this->c ? mysqli_error( $this->c ) : 0
            ]
        ]
        ;
    }

    # Returns [string]
    public function get_error_text ()
    {
        // (Getting the value)
        $errors = $this->get_errors();



        if ( $errors['connection']['error_message'] )
        {// (There is a connection error)
            // (Getting the value)
            $error_text = $errors['connection']['error_code'] . ' -> ' . $errors['connection']['error_message'];
        }
        else
        if ( $errors['execution']['error_message'] )
        {// (There is an execution error)
            // (Getting the value)
            $error_text = $errors['execution']['error_code'] . ' -> '. $errors['execution']['error_message'];
        }
        else
        {// (There are no errors)
            // (Setting the value)
            $error_text = '';
        }



        // Returning the value
        return $error_text;
    }



    # Returns [self|false]
    public function set_var (string $key, string $value)
    {
        // (Getting the value)
        $query = "SET $key = $value;";

        if ( !$this->execute( $query ) )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false]
    public function set_foreign_key_check (bool $value)
    {
        if ( $this->set_var( 'foreign_key_checks', $value ? '1' : '0' ) === false )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [string]
    public function get_insert_mode ()
    {
        // Returning the value
        return $this->insert_mode;
    }

    # Returns [self|false] | Throws [Exception]
    public function set_insert_mode (string $value = 'standard')
    {
        switch ( $value )
        {
            case 'standard':
            case 'empty_text_as_null':
                // (Doing nothing)
            break;

            default:
                // (Setting the value)
                $message = "Cannot set the insert mode :: Value '$value' is not a valid option";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
        }



        // (Getting the value)
        $this->insert_mode = $value;



        // Returning the value
        return $this;
    }



    # Returns [self|false] | Throws [Exception]
    public function select_db (string $value)
    {
        if ( !$this->c )
        {// (Connection has not been open)
            if ( !$this->open() )
            {// (Unable to open the connection)
                // (Setting the value)
                $message = "Unable to open the connection :: " . $this->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }

        if ( !mysqli_select_db( $this->c, $value ) )
        {// (Unable to select the database)
            // (Setting the value)
            $message = "Unable to select the database";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [string|false] | Throws [Exception]
    public function sanitize_text (string $text)
    {
        if ( !$this->c )
        {// (Connection has not been open)
            if ( !$this->open() )
            {// (Unable to open the connection)
                // (Setting the value)
                $message = "Unable to open the connection :: " . $this->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }



        // Returning the value
        return mysqli_real_escape_string( $this->c, $text );
    }

    # Returns [string|false] | Throws [Exception]
    public function normalize_value ($value)
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
                    // (Setting the value)
                    $message = "Unable to encode the value as JSON";

                    // Throwing an exception
                    throw new \Exception($message);
                
                    // Returning the value
                    return false;
                }



                // (Replacing the value)
                $value = str_replace( '\'', '\\\'', $value );
                $value = str_replace( "\\\"", "\\\\\"", $value );
                $value = preg_replace( '/\\\\u([0-9a-f]{4})/', '\\\\\\\\u$1', $value );



                // (Getting the value)
                $value = "'$value'";
            break;

            case 'string':
                // (Getting the value)
                $value = $this->sanitize_text( $value );

                if ( $value === false )
                {// (Unable to sanitize the text)
                    // (Setting the value)
                    $message = "Unable to sanitize the text";

                    // Throwing an exception
                    throw new \Exception($message);

                    // Returning the value
                    return false;
                }



                // (Getting the value)
                $value = "'$value'";
            break;
        }



        // Returning the value
        return $value;
    }



    # Returns [string|false] | Throws [Exception]
    public function fill_vars (string $text, array $kv_data)
    {
        foreach ($kv_data as $k => $v)
        {// Processing each entry
            // (Normalizing the value)
            $nv = $this->normalize_value( $v );

            if ( $nv === false )
            {// (Unable to normalize the value)
                // (Setting the value)
                $message = "Unable to normalize the value";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }



            // (Getting the value)
            $text = str_replace( "{! $k !}", $v, $text );
            $text = str_replace( "{{ $k }}", $nv, $text );
        }



        // Returning the value
        return $text;
    }



    # Returns [self|false] | Throws [Exception]
    public function execute (string $query, array $kv_data = [], ?string &$query_debug = '')
    {
        if ( !$this->c )
        {// (Connection has not been open)
            if ( !$this->open() )
            {// (Unable to open the connection)
                // (Setting the value)
                $message = "Unable to open the connection :: " . $this->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }



        // (Setting the value)
        $this->mysqli_result = null;



        // (Filling the variables)
        $query = $this->fill_vars( $query, $kv_data );

        if ( $query === false )
        {// (Unable to fill the variables)
            // (Setting the value)
            $message = "Unable to fill the variables";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // (Triggering the event)
        $this->trigger_event
        (
            'before-execute',
            [
                'connection' => $this,
                'query'      => $query
            ]
        )
        ;



        if ( $query_debug !== '' )
        {// Value found
            // (Getting the value)
            $query_debug = $query;



            // Returning the value
            return $this;
        }

        if ( $this->debug )
        {// (Debug is enabled)
            // (Appending the value)
            $this->queries[] = $query;
        }



        // (Getting the result)
        $result = mysqli_query( $this->c, $query );

        if ( $result === false )
        {// (Unable to execute the query)
            /*

            if ( self::DEBUG_MODE )
            {// Value is true
                // (Getting the value)
                $message = "Unable to execute the query '$query' :: " . $this->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }

            */



            // (Triggering the event)
            $this->trigger_event
            (
                'error',
                [
                    'connection' => $this,
                    'query'      => $query
                ]
            )
            ;



            // Returning the value
            return false;
        }
        else
        if ($result === true)
        {// (The query command has been executed correctly)
            // Returning the value
            return $this;
        }
        else
        {// (Response-Type is a 'mysqli_result')
            // (Getting the value)
            $this->mysqli_result = $result;
        }



        // Returning the value
        return $this;
    }

    # Returns [self|false] | Throws [Exception]
    public function execute_raw (string $query, array $kv_data = [], ?string &$query_debug = '')
    {
        if ( !$this->c )
        {// (Connection has not been open)
            if ( !$this->open() )
            {// (Unable to open the connection)
                // (Setting the value)
                $message = "Unable to open the connection :: " . $this->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }



        // (Filling the variables)
        $query = $this->fill_vars( $query, $kv_data );

        if ( $query === false )
        {// (Unable to fill the variables)
            // (Setting the value)
            $message = "Unable to fill the variables";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( $query_debug !== '' )
        {// Value found
            // (Getting the value)
            $query_debug = $query;



            // Returning the value
            return $this;
        }

        if ($this->debug)
        {// (Debug is enabled)
            // (Appending the value)
            $this->queries[] = $query;
        }



        // (Executing the multiple queries)
        $result = mysqli_multi_query( $this->c, $query );

        if ( !$result )
        {// (Unable to execute multiple queries)
            /*

            if ( self::DEBUG_MODE )
            {// Value is true
                // (Getting the value)
                $message = "Unable to execute the command '$query' :: " . $this->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }

            */



            // (Triggering the event)
            $this->trigger_event
            (
                'error',
                [
                    'connection' => $this,
                    'query'      => $query
                ]
            )
            ;



            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [Cursor]
    public function fetch_cursor ()
    {
        // (Creating a Cursor)
        $cursor = new Cursor( $this->mysqli_result );

        // (Setting the connection)
        $cursor->set_connection( $this );



        // Returning the value
        return $cursor;
    }



    # Returns [string]
    public function get_last_insert_id ()
    {
        // Returning the value
        return (string) mysqli_insert_id( $this->c );
    }



    # Returns [self]
    public function add_event_listener (string $type, callable $callback)
    {
        // (Appending the value)
        $this->event_listeners[ $type ][] = $callback;



        // Returning the value
        return $this;
    }

    # Returns [self]
    public function trigger_event (string $type, array $data = [])
    {
        foreach ($this->event_listeners[ $type ] as $event_listener)
        {// Processing each entry
            // (Calling the function)
            $event_listener( $data );
        }



        // Returning the value
        return $this;
    }



    # Returns [Entity]
    public function fetch_entity (string $database, string $table)
    {
        // Returning the value
        return new Entity( $this, $database, $table );
    }



    # Returns [string]
    public function get_timezone_hms (int $depth = 3)
    {
        // Returning the value
        return implode( ':', array_slice( explode( ':', $this->timezone_hms ), 0, $depth ) );
    }



    # Returns [self]
    public function set_column_separator (?string $value = null)
    {
        // (Getting the value)
        $this->column_separator = $value;



        // Returning the value
        return $this;
    }



    # Returns [assoc|false]
    public function describe (string $database, string $table)
    {
        // (Getting the values)
        $database = str_replace( '`', '', $database );
        $table    = str_replace( '`', '', $table );



        if ( !$this->execute( "DESCRIBE `$database`.`$table`;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // (Setting the value)
        $fields = [];



        // (Getting the value)
        $records = $this->fetch_cursor()->to_array();

        foreach ( $records as $record )
        {// Processing each entry
            // (Getting the value)
            $field = $record['Field'];

            // (Removing the element)
            unset( $record['Field'] );



            // (Getting the value)
            $fields[ $record['Field'] ] = $field;
        }



        // Returning the value
        return $fields;
    }



    # Returns [void]
    public function __destruct ()
    {
        if ( $this->c )
        {// (Connection is open)
            // (Closing the connection)
            $this->close();
        }
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return json_encode( get_object_vars( $this ) );
    }
}



?>
<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Entity;

use \Solenoid\MySQL\Cursor\Cursor;
use \Solenoid\MySQL\Cursor\BufferedCursor;
use \Solenoid\MySQL\Cursor\UnbufferedCursor;



class Connection
{
    private ?\mysqli $c;



    public ?string  $host;
    public ?int     $port;

    public ?string  $username;
    public ?string  $password;

    public ?string  $database;

    public string   $simulated_command;

    private ?string  $socket;

    private string   $charset;

    private          $debug;
    private          $queries;

    private          $mysqli_result = null;
    private          $mysqli_stmt = null;

    private string   $insert_mode;

    private array    $event_listeners;

    private string   $timezone_hms;

    private ?string  $column_separator;



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



    public function set_debug (bool $value) : self
    {
        // (Getting the value)
        $this->debug = $value;



        // Returning the value
        return $this;
    }

    public function set_charset (string $value) : self
    {
        // (Getting the value)
        $this->charset = $value;



        // Returning the value
        return $this;
    }



    public function open () : self|false
    {
        if ( $this->c ) return $this;



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



        if ( $this->execute( new Command( "SELECT TIMEDIFF( NOW(), UTC_TIMESTAMP ) AS 'timezone';" ) ) === false )
        {// (Unable to get the timezone HMS)
            // Returning the value
            return false;
        }

        // (Getting the value)
        $this->timezone_hms = $this->cursor()->value();
        $this->timezone_hms = $this->timezone_hms[0] === '-' ? $this->timezone_hms : '+' . $this->timezone_hms;



        // (Triggering the event)
        $this->trigger_event( 'open', [ 'connection' => $this ] );



        // Returning the value
        return $this;
    }

    public function close () : self|false
    {
        if ( !mysqli_close( $this->c ) )
        {// (Unable to close the connection)
            // Returning the value
            return false;
        }



        // (Setting the value)
        $this->c = null;



        // (Triggering the event)
        $this->trigger_event( 'close', [ 'connection' => $this ] );



        // Returning the value
        return $this;
    }



    public function get_error_code () : int
    {
        // Returning the value
        return mysqli_errno( $this->c );
    }

    public function get_error_msg () : string
    {
        // Returning the value
        return mysqli_error( $this->c );
    }

    public function get_errors () : array
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

    public function get_error_text () : string
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



    public function set_var (string $key, string $value) : self|false
    {
        // (Getting the value)
        $query = "SET $key = $value;";

        if ( !$this->execute( new Command( $query ) ) )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    public function set_foreign_key_check (bool $value) : self|false
    {
        if ( $this->set_var( 'foreign_key_checks', $value ? '1' : '0' ) === false )
        {// (Unable to execute the query)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    public function get_insert_mode () : string
    {
        // Returning the value
        return $this->insert_mode;
    }

    public function set_insert_mode (string $value = 'standard') : self|false
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



    public function select_db (string $value) : self|false
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



    public function sanitize_text (string $text) : string|false
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

    public function normalize_value ($value) : string|false
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
            case 'object':
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



    public function free_result () : self
    {
        if ( $this->mysqli_result )
        {// Value found
            // (Freeing the memory)
            mysqli_free_result( $this->mysqli_result );



            // (Setting the value)
            $this->mysqli_result = null;
        }



        while ( mysqli_more_results( $this->c ) && mysqli_next_result( $this->c ) ) 
        {// Processing each entry
            if ( $result = mysqli_store_result( $this->c ) ) 
            {// (Result found)
                // (Freeing the memory)
                mysqli_free_result( $result );
            }
        }



        // Returning the value
        return $this;
    }



    public function execute (Command $command, bool $stream = false) : self|false
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



        // (Freeing the result)
        $this->free_result();



        // (Setting the connection)
        $command->set_connection( $this );



        // (Setting the value)
        $ordered_values = [];

        // (Getting the value)
        $prepared_command = preg_replace_callback
        (
            '/(?<!:):([a-zA-Z0-9_]+)/',
            function ($matches) use ($command, &$ordered_values)
            {
                // (Getting the value)
                $key = $matches[1];

                if ( !array_key_exists( $key, $command->values ) )
                {// Value not found
                    // Throwing the exception
                    throw new \Exception( "Missing value for named parameter :$key" );
                }



                // (Appending the value)
                $ordered_values[] = $command->values[ $key ];



                // Returning the value
                return '?'; 
            },
            $command->sql
        )
        ;



        // (Getting the value)
        $this->simulated_command = $command->simulate();



        // (Getting the value)
        $stmt = mysqli_prepare( $this->c, $prepared_command );

        if ( !$stmt )
        {// (Unable to prepare the statement)
            // (Triggering the event)
            $this->trigger_event( 'error', [ 'connection' => $this, 'command' => $this->simulated_command, 'message' => 'Unable to prepate the statement' ] );



            // Returning the value
            return false;
        }



        if ( $ordered_values )
        {// Value is not empty
            // (Setting the values)
            $types  = '';
            $params = [];

            foreach ( $ordered_values as $v )
            {// Processing each entry
                if ( is_int( $v ) ) $types .= 'i';
                else
                if ( is_float( $v ) ) $types .= 'd';
                else
                if ( is_bool( $v ) )
                {// Match OK
                    // (Appending the value) 
                    $types .= 'i'; 

                    // (Getting the value)
                    $v = $v ? 1 : 0; 
                }
                else
                if ( is_null( $v ) ) $types .= 's'; 
                else $types .= 's';



                // (Appending the value)
                $params[] = $v;
            }



            // (Binding the params)
            mysqli_stmt_bind_param( $stmt, $types, ...$params );
        }



        // (Triggering the event)
        $this->trigger_event( 'before-execute', [ 'connection' => $this, 'command' => $this->simulated_command ] );



        // (Triggering the event)
        $this->trigger_event( 'command', [ 'connection' => $this, 'command' => $this->simulated_command ] );



        if ( $this->debug )
        {// (Debug is enabled)
            // (Appending the value)
            $this->queries[] = $this->simulated_command;
        }



        if ( !mysqli_stmt_execute( $stmt ) )
        {// (Unable to execute the statement)
            // (Triggering the event)
            $this->trigger_event( 'error', [ 'connection' => $this, 'command' => $this->simulated_command, 'message' => 'Unable to execute the statement' ] );



            // (Closing the statement)
            mysqli_stmt_close( $stmt );



            // Returning the value
            return false;
        }



        if ( $stream )
        {// (Mode is 'Unbuffered')
            // (Getting the value)
            $this->mysqli_stmt = $stmt;



            // (Setting the value)
            $this->mysqli_result = null;
        }
        else
        {// (Mode is 'Buffered')
            // (Getting the value)
            $result = mysqli_stmt_get_result( $stmt );

            if ( $result !== false )
            {// (Result found)
                // (Getting the value)
                $this->mysqli_result = $result;
            }



            // (Closing the statement)
            mysqli_stmt_close( $stmt );



            // (Setting the value)
            $this->mysqli_stmt = null;
        }



        // Returning the value
        return $this;
    }



    public function cursor () : Cursor
    {
        // (Getting the value)
        $cursor = $this->mysqli_result ? new BufferedCursor( $this->mysqli_result ) : new UnbufferedCursor( $this->mysqli_stmt );



        // (Setting the connection)
        $cursor->set_connection( $this );



        // Returning the value
        return $cursor;
    }



    public function get_last_insert_id () : string
    {
        // Returning the value
        return (string) ( $this->c ? mysqli_insert_id( $this->c ) : 0 );
    }



    public function on (string $event_type, callable $callback) : self
    {
        // (Appending the value)
        $this->event_listeners[ $event_type ][] = $callback;



        // Returning the value
        return $this;
    }

    public function trigger_event (string $type, array $data = []) : self
    {
        foreach ( $this->event_listeners[ $type ] ?? [] as $event_listener )
        {// Processing each entry
            // (Calling the function)
            $event_listener( $data );
        }



        // Returning the value
        return $this;
    }



    public function fetch_entity (string $database, string $table) : Entity
    {
        // Returning the value
        return new Entity( $this, $database, $table );
    }



    public function get_timezone_hms (int $depth = 3) : string
    {
        // Returning the value
        return implode( ':', array_slice( explode( ':', $this->timezone_hms ), 0, $depth ) );
    }



    public function set_column_separator (?string $value = null) : self
    {
        // (Getting the value)
        $this->column_separator = $value;



        // Returning the value
        return $this;
    }



    public function describe (string $database, string $table) : array|false
    {
        // (Getting the values)
        $database = str_replace( '`', '', $database );
        $table    = str_replace( '`', '', $table );



        if ( !$this->execute( new Command( "DESCRIBE `$database`.`$table`;" ) ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // (Setting the value)
        $fields = [];



        // (Getting the value)
        $records = $this->cursor()->list();

        foreach ( $records as $record )
        {// Processing each entry
            // (Getting the value)
            $field = $record->Field;

            // (Removing the element)
            unset( $record->Field );



            // (Getting the value)
            $fields[ $field ] = (array) $record;
        }



        // Returning the value
        return $fields;
    }



    public function __destruct ()
    {
        if ( $this->c )
        {// (Connection is open)
            // (Closing the connection)
            $this->close();
        }
    }



    public function __toString () : string
    {
        // Returning the value
        return json_encode( get_object_vars( $this ) );
    }
}



?>
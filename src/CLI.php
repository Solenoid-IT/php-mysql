<?php



namespace Solenoid\MySQL;



class CLI
{
    # Returns [bool]
    public static function export
    (
        string $host                  = 'localhost',
        int    $port                  = 3306,

        string $username              = 'root',
        string $password              = '',

        array  $databases             = [],

        ?string $file_path            = null
    )
    {
        // (Getting the values)
        $host          = escapeshellarg( $host );
        $port          = escapeshellarg( $port );

        $username      = escapeshellarg( $username );
        $password      = escapeshellarg( $password );

        $databases_raw = $databases ? '--databases ' . implode( ' ' , array_map( function ($database) { return escapeshellarg( $database ); }, $databases ) ) : '--all-databases';

        $file_path     = $file_path ?? date('Ymd--His') . '.sql';



        // (Executing the command)
        $result = shell_exec("mysqldump --no-tablespaces --host=$host --port=$port --user=$username -p$password $databases_raw -r $file_path 2>&1");

        if ( $result !== null )
        {// (Unable to export the databases)
            // (Getting the value)
            $result = str_replace( "\n", ' >> ', $result );



            // (Setting the value)
            $message = "Unable to export the databases :: `$result`";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return true;
    }

    # Returns [bool]
    public static function import
    (
        string $username  = 'root',
        string $password  = '',

        string $file_path
    )
    {
        // (Getting the values)
        $username  = escapeshellarg( $username );
        $password  = escapeshellarg( $password );

        $file_path = escapeshellarg( $file_path );



        // (Executing the command)
        $result = shell_exec("mysql -u $username -p$password < $file_path 2>&1");

        if ( $result !== null )
        {// (Unable to import the databases)
            // (Getting the value)
            $result = str_replace( "\n", ' >> ', $result );



            // (Setting the value)
            $message = "Unable to import the databases :: `$result`";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return true;
    }
}



?>
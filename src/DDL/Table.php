<?php



namespace Solenoid\MySQL\DDL;



class Table
{
    public string $name;
    public string $engine;

    public array  $fields;



    # Returns [self]
    public function __construct (string $name, string $engine)
    {
        // (Getting the values)
        $this->name   = $name;
        $this->engine = $engine;
    }

    # Returns [Table]
    public static function create (string $name, string $engine)
    {
        // Returning the value
        return new Table( $name, $engine );
    }



    # Returns [self]
    public function add_field (string $name, string $type = 'VARCHAR(255)', bool $null = false, bool $ai = false)
    {
        // (Appending the value)
        $this->fields[ $name ] =
        [
            'type' => $type,
            'null' => $null,
            'ai'   => $ai
        ]
        ;



        // Returning the value
        return $this;
    }



    # Returns [string]
    public function __toString ()
    {
        // (Setting the value)
        $columns = [];

        foreach ( $this->fields as $field )
        {
            // (Appending the value)
            $columns[] = '`' . $field['name'] . '`' . ' ' . $field['type'] . ( $field['null'] ? ' NULL' : ' NOT NULL' ) . ( $field['ai'] ? ' AUTO_INCREMENT' : '' );
        }



        // (Getting the value)
        $columns = implode( "\n\t", $columns );



        // Returning the value
        return
            <<<EOD
            CREATE TABLE `$this->name`
            (
                $columns
            )
            ENGINE $this->engine
            ;
            EOD
        ;
    }
}



?>
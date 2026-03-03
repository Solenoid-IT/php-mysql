<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Condition;
use \Solenoid\MySQL\Cursor;



class Query
{
    private Connection $connection;
    private ?string    $name;

    private string     $source;
    private Condition  $condition;
    private array      $group;
    private string     $having_raw;
    private array      $order;
    private string     $limit;

    private array      $projection;
    private bool       $distinct;



    public function __construct (Connection $connection, ?string $name = null)
    {
        // (Getting the values)
        $this->connection = $connection;
        $this->name       = $name;



        // (Setting the values)
        $this->source     = '';
        $this->group      = [];
        $this->having_raw = '';
        $this->order      = [];
        $this->limit      = '';

        $this->projection = [];
        $this->distinct   = false;
    }



    public function from_raw (string $content) : self
    {
        // (Appending the value)
        $this->source .= $content;



        // Returning the value
        return $this;
    }



    public function from (string $table, ?string $alias = null, ?string $database = null, bool $replace = false) : self
    {
        // (Getting the value)
        $content = ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' );

        if ( $replace )
        {// Value is true
            // (Getting the value)
            $this->source = $content;
        }
        else
        {// Value is false
            // (Appending the value)
            $this->from_raw( $content );
        }



        // Returning the value
        return $this;
    }



    public function natural_join (string $table, ?string $alias = null, ?string $database = null) : self
    {
        // (Appending the value)
        $this->from_raw( ' NATURAL JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }

    public function cross_join (string $table, ?string $alias = null, ?string $database = null) : self
    {
        // (Appending the value)
        $this->from_raw( ' CROSS JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    public function inner_join (string $table, ?string $alias = null, ?string $database = null) : self
    {
        // (Appending the value)
        $this->from_raw( ' INNER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    public function left_outer_join (string $table, ?string $alias = null, ?string $database = null) : self
    {
        // (Appending the value)
        $this->from_raw( ' LEFT OUTER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }

    public function right_outer_join (string $table, ?string $alias = null, ?string $database = null) : self
    {
        // (Appending the value)
        $this->from_raw( ' RIGHT OUTER JOIN ' . ( $database ? '`' . $this->connection->sanitize_text( str_replace( '`', '', $database ) ) . '`' . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $table ) ) . '`' . ( $alias ? ' ' . $this->connection->sanitize_text($alias) : '' ) );



        // Returning the value
        return $this;
    }



    public function on (string $a, string $op, string $b, ?string $a_table_alias = null, ?string $b_table_alias = null) : self
    {
        // (Getting the value)
        $a_table_alias = $a_table_alias ? ( $this->connection->sanitize_text( str_replace( '`', '', $a_table_alias ) ) . '.' ) : '';
        $a             = '`' . $this->connection->sanitize_text( str_replace( '`', '', $a ) ) . '`';

        $b_table_alias = $b_table_alias ? ( $this->connection->sanitize_text( str_replace( '`', '', $b_table_alias ) ) . '.' ) : '';
        $b             = '`' . $this->connection->sanitize_text( str_replace( '`', '', $b ) ) . '`';




        // (Appending the value)
        $this->from_raw( "{$a_table_alias}$a $op {$b_table_alias}$b" );



        // Returning the value
        return $this;
    }

    public function on_and () : self
    {
        // (Appending the value)
        $this->from_raw( ' AND ' );



        // Returning the value
        return $this;
    }

    public function on_or () : self
    {
        // (Appending the value)
        $this->from_raw( ' OR ' );



        // Returning the value
        return $this;
    }



    public function condition (?Condition $condition = null) : self
    {
        // (Getting the value)
        $this->condition = $condition ?? ( new Condition() )->set_connection( $this->connection )->set_query( $this );



        // Returning the value
        return $this;
    }

    public function condition_start () : Condition
    {
        // (Getting the value)
        $this->condition = ( new Condition() )->set_connection( $this->connection )->set_query( $this );



        // Returning the value
        return $this->condition;
    }



    public function group_by (string $column, ?string $table_alias = null) : self
    {
        // (Appending the value)
        $this->group[] = ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column ) ) . '`';



        // Returning the value
        return $this;
    }

    public function having (string $expression) : self
    {
        // (Getting the value)
        $this->having_raw = $expression;



        // Returning the value
        return $this;
    }



    public function order_by (string $column, string $direction, ?string $table_alias = null) : self
    {
        // (Appending the value)
        $this->order[] = ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column ) ) . '`' . ' ' . ( $direction === 'ASC' ? 'ASC' : 'DESC' );



        // Returning the value
        return $this;
    }



    public function limit (int $value) : self
    {
        // (Appending the value)
        $this->limit .= 'LIMIT ' . $value;



        // Returning the value
        return $this;
    }

    public function limit_range (int $min, int $max) : self
    {
        // (Appending the value)
        $this->limit .= 'LIMIT ' . $min . ', ' . $max;



        // Returning the value
        return $this;
    }

    public function offset (int $value) : self
    {
        // (Appending the value)
        $this->limit .= ' OFFSET ' . $value;



        // Returning the value
        return $this;
    }



    public function distinct () : self
    {
        // (Setting the value)
        $this->distinct = true;



        // Returning the value
        return $this;
    }



    public function select_raw (string $content) : self
    {
        // (Appending the value)
        $this->projection[] = $content;



        // Returning the value
        return $this;
    }

    public function select_field (string $column, ?string $table_alias = null, ?string $name = null) : self
    {
        // (Appending the value)
        $this->select_raw( ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column ) ) . '`' . ( $name ? ' AS ' . '`' . $this->connection->sanitize_text( str_replace( '`', '', $name ) ) . '`' : '' ) );



        // Returning the value
        return $this;
    }

    public function select_all (?string $table_alias = null) : self
    {
        // (Appending the value)
        $this->select_raw( ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '*' );



        // Returning the value
        return $this;
    }

    public function select_agg (string $type, string $column, ?string $table_alias = null, ?string $name = null) : self
    {
        // (Appending the value)
        $this->select_raw( $type . '( ' . ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $column ) ) . '`' . ' ) ' . ( $name ? ' AS ' . '`' . $this->connection->sanitize_text( str_replace( '`', '', $name ) ) . '`' : '' ) );



        // Returning the value
        return $this;
    }



    public function count_all (?string $name = null, ?string $table_alias = null) : self
    {
        // (Appending the value)
        $this->select_raw( 'COUNT( ' . ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '*' . ' ) ' . ( $name ? ' AS ' . '`' . $this->connection->sanitize_text( str_replace( '`', '', $name ) ) . '`' : '' ) );



        // Returning the value
        return $this;
    }

    public function count_field (string $name, ?string $table_alias = null) : self
    {
        // (Appending the value)
        $this->select_raw( 'COUNT( DISTINCT ' . ( $table_alias ? $this->connection->sanitize_text( $table_alias ) . '.' : '' ) . '`' . $this->connection->sanitize_text( str_replace( '`', '', $name ) ) . '`' . ' )' );



        // Returning the value
        return $this;
    }



    public function run (bool $stream = false) : Cursor|false
    {
        // (Getting the value)
        $command = new Command( (string) $this, isset( $this->condition ) ? $this->condition->values : [] );

        if ( !$this->connection->execute( $command, stream: $stream ) )
        {// (Unable to execute the command)
            // Throwing the exception
            throw new \Exception( "Unable to execute the query '$this->name' :: " . $this->connection->get_error_text() );
        }



        // Returning the value
        return $this->connection->cursor();
    }



    public function build (string $delimiter = ';') : string
    {
        // (Getting the values)
        $projection = implode( ",\n\t", $this->projection );
        $distinct   = $this->distinct ? 'DISTINCT' : '';
        $source     = $this->source;
        $condition  = $this->condition;

        $group_by   = $this->group ? 'GROUP BY' . "\n\t" . implode( ",\n\t", $this->group ) : '';
        $order_by   = $this->order ? 'ORDER BY' . "\n\t" . implode( ",\n\t", $this->order ) : '';

        $limit      = $this->limit;



        // (Setting the value)
        $command = '';



        // (Appending the value)
        $command .= "SELECT $distinct\n\t$projection\nFROM\n\t$source\nWHERE\n\t$condition\n";

        if ( $this->group )
        {// Value is not empty
            // (Appending the value)
            $command .= "$group_by\n";
        }

        if ( $this->having_raw )
        {// Value found
            // (Appending the value)
            $command .= "HAVING\n\t$this->having_raw\n";
        }

        if ( $this->order )
        {// Value is not empty
            // (Appending the value)
            $command .= "$order_by\n";
        }

        if ( $this->limit )
        {// Value found
            // (Appending the value)
            $command .= "$limit\n";
        }



        // (Appending the value)
        $command .= $delimiter;



        // Returning the value
        return $command;
    }



    public function __toString () : string
    {
        // Returning the value
        return $this->build();
    }
}



?>
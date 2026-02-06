<?php



namespace Solenoid\MySQL;



use \Solenoid\MySQL\Connection;
use \Solenoid\MySQL\Condition;
use \Solenoid\MySQL\Relation;



class Model
{
    private int $lid;

    private array $rels;
    private array $links;



    public Connection $connection;
    public string     $database;
    public string     $table;

    public ?Condition $condition;

    public array      $group_columns;
    public array      $order_columns;

    public string     $having_raw;

    public int        $limit;
    public ?int       $offset;



    private function build_query (array $fields = [], bool $exclude_fields = false) : Query
    {
        // (Getting the value)
        $condition = $this->condition ?? ( new Condition() )->set_connection( $this->connection );



        // (Getting the value)
        $query = $this->query()->condition( $condition );

        

        // (Getting the value)
        $fields = array_unique( $fields );

        if ( $fields )
        {// Value is not empty
            if ( $exclude_fields )
            {// (Fields are excluded)
                // (Getting the value)
                $table_fields = array_keys( $this->describe() );



                // (Getting the value)
                $include_fields = array_diff( $table_fields, $fields );

                foreach ( $include_fields as $field )
                {// Processing each entry
                    // (Composing the query)
                    $query->select_field( null, $field );
                }
            }
            else
            {// (Fields are included)
                foreach ( $fields as $field )
                {// Processing each entry
                    // (Composing the query)
                    $query->select_field( null, $field );
                }
            }
        }
        else
        {// Value is empty
            // (Composing the query)
            $query->select_all();
        }



        foreach ( $this->group_columns as $column )
        {// Processing each entry
            // (Composing the query)
            $query->group_by( null, $column );
        }

        if ( $this->having_raw )
        {// Value found
            // (Composing the query)
            $query->having( $this->having_raw );
        }



        foreach ( $this->order_columns as $column => $direction )
        {// Processing each entry
            // (Composing the query)
            $query->order_by( null, $column, $direction );
        }



        if ( isset( $this->limit ) )
        {// Value is set
            // (Composing the query)
            $query->limit( $this->limit );
        }

        if ( isset( $this->offset ) )
        {// Value is set
            // (Composing the query)
            $query->offset( $this->offset );
        }



        // Returning the value
        return $query;
    }



    private function get_relation (string|object $model) : Relation|null
    {
        // (Getting the value)
        $class = $model;

        if ( is_object( $model ) )
        {// Match OK
            // (Getting the value)
            $class = get_class( $model );
        }



        if ( !class_exists( $class ) ) return null;



        // Returning the value
        return new Relation( $class );
    }

    private function get_related_model (string|object $model) : self
    {
        if ( is_string( $model ) )
        {// Match OK
            if ( is_subclass_of( $model, __CLASS__ ) )
            {// Match OK
                // (Getting the value)
                $default_props = ( new \ReflectionClass( $model ) )->getDefaultProperties();

                // (Getting the value)
                $model = new self( $this->connection, $default_props['database'], $default_props['table'] );
            }
        }



        // Returning the value
        return $model;
    }

    private function load_links (array $records)
    {
        foreach ( $this->links as $link )
        {// Processing each entry
            if ( is_array( $link ) )
            {// Match OK
                // (Getting the values)
                [ $related_model, $fields ] = $link;
            }
            else
            {// Match failed
                // (Getting the value)
                $related_model = $link;

                // (Setting the value)
                $fields = [];
            }



            // (Getting the value)
            $relation = $this->get_relation( $related_model ); 



            // (Setting the value)
            $primary_keys = [];

            foreach ( $records as $record )
            {// Processing each entry
                // (Getting the value)
                $pk_value = $record->{ $relation->local_key };

                if ( $pk_value )
                {// Value found
                    // (Appending the value)
                    $primary_keys[] = $pk_value;
                }
            }



            // (Getting the value)
            $primary_keys = array_unique( $primary_keys );

            if ( !$primary_keys ) continue;



            // (Getting the value)
            $related_model = $this->get_related_model( $relation->model );



            // (Getting the value)
            $related_results = $related_model->where( $relation->foreign_key, 'IN', $primary_keys )->list( [ $relation->foreign_key, ...$fields ] );



            // (Getting the value)
            $fk_field = in_array( $relation->foreign_key, $fields );



            // (Setting the value)
            $remote_records = [];

            foreach ( $related_results as $related_record )
            {// Processing each entry
                // (Getting the value)
                $fk_value = $related_record->{ $relation->foreign_key };



                if ( !$fk_field )
                {// (Fk field is not required)
                    // (Removing the element)
                    #unset( $related_record->{ $relation->foreign_key } );
                }



                // (Appending the value)
                $remote_records[ $fk_value ][] = $related_record;
            }



            foreach ( $records as $record )
            {// Processing each entry
                // (Getting the value)
                $pk_value = $record->{ $relation->local_key };

                // (Getting the value)
                $related_data = $remote_records[ $pk_value ] ?? [];

                // (Setting the relation)
                $record->set_relation( $related_model->table, $related_data );
            }
        }



        // Returning the value
        return $records;
    }



    public function __construct (Connection &$connection, string $database, string $table)
    {
        // (Getting the values)
        $this->connection    = &$connection;
        $this->database      = str_replace( '`', '', $database );
        $this->table         = str_replace( '`', '', $table );

        $this->condition     = null;
        
        $this->group_columns = [];
        $this->order_columns = [];

        $this->having_raw    = '';



        // (Setting the value)
        $this->rels = [];



        // (Setting the value)
        $this->links = [];



        // (Resetting the model)
        $this->reset();
    }



    public function insert (array $records, bool $ignore_error = false) : self|false
    {
        // (Getting the value)
        $this->lid = (int) $this->connection->get_last_insert_id();



        // (Getting the value)
        $columns = implode( ',', array_map( function ($column) { $column = str_replace( '`', '', $column ); return "`$column`"; }, array_keys( $records[0] ) ) );



        // (Setting the value)
        $values = [];

        foreach ( $records as $record )
        {// Processing each entry
            // (Appending the value)
            $values[] = '(' . implode( ',', array_map( function ($v) { return $this->connection->normalize_value( $v ); }, array_values( $record ) ) ) . ')';
        }



        // (Getting the value)
        $values = implode( ",\n", $values );



        // (Getting the value)
        $ignore = $ignore_error ? ' IGNORE' : '';



        // (Getting the value)
        $query =
            <<<EOD
            INSERT$ignore INTO `$this->database`.`$this->table` ($columns)
            VALUES
                $values
            ;
            EOD
        ;

        if ( !$this->connection->execute( $query ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    public function select (Query $query) : Cursor|false
    {
        // Returning the value
        return $query->from( $this->database, $this->table, 'T', true )->run();
    }

    public function update (array $values) : self|false
    {
        // (Getting the value)
        $condition = $this->condition ?? ( new Condition() )->set_connection( $this->connection );



        // (Setting the value)
        $kv_values = [];

        foreach ( $values as $k => $v )
        {// Processing each entry
            // (Appending the value)
            $kv_values[] = '`' . str_replace( '`', '', $k ) . '`' . ' = ' . $this->connection->normalize_value( $v );
        }



        // (Getting the value)
        $kv_values = implode( ",\n\t", $kv_values );



        // (Getting the value)
        $cmd =
            <<<EOD
            UPDATE `$this->database`.`$this->table`
            SET
                $kv_values
            WHERE
                $condition
            ;
            EOD
        ;

        if ( !$this->connection->execute( $cmd ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }

    public function delete () : self|false
    {
        // (Getting the value)
        $condition = $this->condition ?? ( new Condition() )->set_connection( $this->connection );



        if ( !$this->connection->execute( "DELETE\nFROM\n\t`$this->database`.`$this->table`\nWHERE\n\t$condition\n;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    public function set (array $values = [], array $key = [], bool $ignore_error = false) : Cursor|false
    {
        // (Getting the value)
        $key_values =
        [
            'normalized' => [],
            'raw'        => []
        ]
        ;

        foreach ($key as $key_component)
        {// Processing each entry
            if ( $values[ $key_component ] )
            {// Value found
                // (Getting the value)
                $key_values['normalized'][ $key_component ] = $values[ $key_component ];
            }
        }



        // (Getting the value)
        $where_kv_data = $key_values['normalized'];



        // (Getting the value)
        $cursor = ( new Query( $this->connection ) )
            ->from( $this->database, $this->table )

            ->condition_start()
                ->filter( [ $where_kv_data ] )
            ->condition_end()

            ->select_all()

            ->run()
        ;

        if ( $cursor->is_empty() )
        {// (Record not found)
            if ( $this->insert( [ $values ], $ignore_error ) === false )
            {// (Unable to insert the record)
                // (Setting the value)
                $message = "Unable to insert the record :: " . $this->connection->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }
        else
        {// (Record found)
            // (Getting the value)
            $n_kv_data = array_diff_assoc( $values, $key_values['normalized'] );

            if ( $this->filter( [ $key_values['normalized'] ] )->update( $n_kv_data ) === false )
            {// (Unable to update the record)
                // (Setting the value)
                $message = "Unable to update the record :: " . $this->connection->get_error_text();

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return false;
            }
        }



        // (Getting the value)
        $cursor = ( new Query( $this->connection ) )
            ->from( $this->database, $this->table )

            ->condition_start()
                ->filter( [ $where_kv_data ] )
            ->condition_end()

            ->select_all()

            ->run()
        ;



        // (Setting the value)
        $this->condition = null;



        // Returning the value
        return $cursor;
    }



    public function query (?string $table_alias = null) : Query
    {
        // (Getting the value)
        $query = new Query( $this->connection );



        // (Composing the query)
        $query->from( $this->database, $this->table, $table_alias, true );



        // Returning the value
        return $query;
    }



    public function filter (array $filter = []) : self
    {
        // (Getting the value)
        $this->condition = ( new Condition() )->set_connection( $this->connection )->set_model( $this );



        // (Composing the condition)
        $this->condition->filter($filter);



        // Returning the value
        return $this;
    }

    public function condition_start () : Condition
    {
        // Returning the value
        return $this->condition = ( new Condition() )->set_connection( $this->connection )->set_model( $this );
    }



    public function count (?string $field = null) : int
    {
        // (Getting the value)
        $query = $this->query()->condition( $this->condition );



        if ( $field )
        {// Value found
            // (Composing the query)
            $query->count_field( null, $field );
        }
        else
        {// Value not found
            // (Composing the query)
            $query->count_all( null, 'num_records' );
        }



        // Returning the value
        return (int) $query->run()->set_mode( 'value' )->fetch_head();
    }

    public function find (array $fields = [], bool $exclude_fields = false, ?callable $transform_record = null) : Record|false
    {
        // (Getting the value)
        $record = $this->build_query( $fields, $exclude_fields )->run()->fetch_head( $transform_record );

        if ( !$record || !$this->links )
        {// Match failed
            // Returning the value
            return $record;
        }



        // Returning the value
        return $this->load_links( [ $record ] )[0];
    }

    # Returns [array<Record>]
    public function list (array $fields = [], bool $exclude_fields = false, ?callable $transform_record = null)
    {
        // (Getting the value)
        $records = $this->build_query( $fields, $exclude_fields )->run()->list( $transform_record );

        if ( !$records || !$this->links )
        {// Match failed
            // Returning the value
            return $records;
        }



        // Returning the value
        return $this->load_links( $records );
    }



    public function cursor (array $fields = [], bool $exclude_fields = false) : Cursor|false
    {
        // (Getting the value)
        $query = $this->build_query( $fields, $exclude_fields );



        // (Getting the value)
        $cursor = $query->run( true );

        if ( !$cursor )
        {// (Unable to run the query)
            // Returning the value
            return false;
        }



        // (Setting the model)
        $cursor->set_model( $this );



        // Returning the value
        return $cursor;
    }



    # Returns [array<int>] | Throws [Exception]
    public function fetch_ids ()
    {
        // (Getting the values)
        $last  = (int) $this->connection->get_last_insert_id();
        $first = $last - $this->lid;



        // (Setting the value)
        $ids = [];

        for ( $i = $first; $i <= $last; $i++ )
        {// Iterating each index
            // (Appending the value)
            $ids[] = $i;
        }



        // Returning the value
        return $ids;
    }

    public function last_id () : int
    {
        // Returning the value
        return (int) $this->connection->get_last_insert_id();
    }



    public function empty () : self|false
    {
        if ( !$this->connection->execute( "TRUNCATE TABLE `$this->database`.`$this->table`;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    # Returns [assoc|false]
    public function describe ()
    {
        // Returning the value
        return $this->connection->describe( $this->database, $this->table );
    }



    public function copy (string $dst_database, string $dst_table, bool $copy_data = false) : self|false
    {
        // (Getting the values)
        $dst_database = str_replace( '`', '', $dst_database );
        $dst_table    = str_replace( '`', '', $dst_table );



        if ( !$this->connection->execute( "CREATE TABLE `$dst_database`.`$dst_table` LIKE `$this->database`.`$this->table`;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        if ( $copy_data )
        {// Value is true
            if ( !$this->connection->execute( "INSERT INTO `$dst_database`.`$dst_table` SELECT * FROM `$this->database`.`$this->table`;" ) )
            {// (Unable to execute the cmd)
                // Returning the value
                return false;
            }
        }



        // Returning the value
        return $this;
    }

    public function remove () : self|false
    {
        if ( !$this->connection->execute( "DROP TABLE IF EXISTS `$this->database`.`$this->table`;" ) )
        {// (Unable to execute the cmd)
            // Returning the value
            return false;
        }



        // Returning the value
        return $this;
    }



    public function reset () : self
    {
        // (Getting the value)
        $this->condition = ( new Condition() )->set_connection( $this->connection )->set_model( $this );



        // Returning the value
        return $this;
    }

    public function where () : self
    {
        // (Composing the condition)
        $this->condition->where( ... func_get_args() );



        // Returning the value
        return $this;
    }

    public function where_tuple (array $fields, string $operator, array $values) : self
    {
        // (Composing the condition)
        $this->condition->where_tuple( $fields, $operator, $values );



        // Returning the value
        return $this;
    }

    public function and () : self
    {
        // (Composing the condition)
        $this->condition->and();



        // Returning the value
        return $this;
    }

    public function or () : self
    {
        // (Composing the condition)
        $this->condition->or();



        // Returning the value
        return $this;
    }

    public function exists () : bool
    {
        // Returning the value
        return $this->count() > 0;
    }



    public function bind (&$object, array $fields = []) : self
    {
        // (Getting the value)
        $object = $this->find( $fields );



        // Returning the value
        return $this;
    }



    public function group (array $columns) : self
    {
        // (Setting the value)
        $this->group_columns = [];

        foreach ( $columns as $column )
        {// Processing each entry
            // (Appending the value)
            $this->group_columns[] = $column;
        }



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



    public function order (array $columns) : self
    {
        // (Setting the value)
        $this->order_columns = [];

        foreach ( $columns as $column => $direction )
        {// Processing each entry
            switch ( $direction )
            {
                case SORT_ASC:
                    // (Setting the value)
                    $direction = 'ASC';
                break;

                case SORT_DESC:
                    // (Setting the value)
                    $direction = 'DESC';
                break;

                default:
                    // (Getting the value)
                    $direction = strtoupper( $direction );
            }



            // (Getting the value)
            $this->order_columns[ $column ] = $direction;
        }



        // Returning the value
        return $this;
    }



    public function fill (array $values) : self
    {
        // (Filling the values)
        $this->condition->fill( $values );



        // Returning the value
        return $this;
    }



    public function search (string $value, string $format = '%V%', array $fields = []) : self
    {
        // (Getting the value)
        $fields = $fields ? $fields : array_keys( $this->describe() );



        // (Getting the value)
        $this->condition->search( $value, $format, $fields );



        // Returning the value
        return $this;
    }

    public function search_values (array $values, string $format = '%V%') : self
    {
        // (Getting the value)
        $this->condition->search_values( $values, $format );



        // Returning the value
        return $this;
    }



    public function paginate (int $limit, ?int $offset = null) : self
    {
        // (Getting the values)
        $this->limit  = $limit;
        $this->offset = $offset;



        // Returning the value
        return $this;
    }



    public function rel (string|object $model, callable $filter) : self|false
    {
        // (Getting the value)
        $relation = $this->get_relation( $model );

        if ( !$relation )
        {// Value not found
            // Returning the value
            return false;
        }



        // (Getting the value)
        $related_model = $this->get_related_model( $relation->model );



        // (Appending the value)
        $this->rels[] = $relation->model;

        // (Getting the value)
        $table_alias = 'R' . count( $this->rels );



        // (Getting the value)
        $condition = $related_model->query( $table_alias )->condition_start()->where_raw( "`$relation->foreign_key` = $table_alias.`$relation->local_key`" )->and(); 



        // (Calling the function)
        $filter( $condition );



        // (Getting the value)
        $sub_query = $condition->condition_end()->select_raw( '1' )->build( '' );



        // (Composing the condition)
        $this->condition->where_raw( "EXISTS\n(\n\t$sub_query\n)" );



        // Returning the value
        return $this;
    }

    public function link (array $models) : self
    {
        // (Getting the value)
        $this->links = $models;


        // Returning the value
        return $this;
    }



    public static function get_keys (string|self $model) : array
    {
        // (Setting the value)
        $keys = [];

        foreach ( ( new \ReflectionClass( $model ) )->getAttributes( Key::class ) as $attribute )
        {// Processing each entry
            // (Appending the value)
            $keys[] = $attribute->newInstance();
        }



        // Returning the value
        return $keys;
    }



    public function __toString () : string
    {
        // Returning the value
        return "`$this->database`.`$this->table`";
    }
}



?>
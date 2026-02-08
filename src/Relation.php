<?php



namespace Solenoid\MySQL;



use \Attribute;



#[ Attribute( Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE ) ]
class Relation
{
    public function __construct
    (
        public string $model,
        public string $type = 'hasMany',
        public string $local_key = 'id',
        public string $foreign_key = 'id'
    )
    {}



    public static function resolve (string $model_class) : self|null
    {
        if ( !is_subclass_of( $model_class, Model::class ) ) return null;



        // (Getting the value)
        $model_name = end( explode( '\\', $model_class ) );

    

        foreach ( ( new \ReflectionClass( $model_class ) )->getAttributes( self::class ) as $attribute )
        {// Processing each entry
            if ( $attribute->getArguments()[0]['name'] !== $model_name ) continue;



            // Returning the value
            return $attribute->newInstance();
        }



        // Returning the value
        return null;
    }
}



?>
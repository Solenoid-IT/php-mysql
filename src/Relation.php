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



    public static function resolve (string $src, string $dst) : self|null
    {
        if ( !is_subclass_of( $src, Model::class ) ) return null;
        if ( !is_subclass_of( $dst, Model::class ) ) return null;

    

        foreach ( ( new \ReflectionClass( $src ) )->getAttributes( self::class ) as $attribute )
        {// Processing each entry
            if ( $attribute->getArguments()[0]['model'] !== $dst ) continue;



            // Returning the value
            return $attribute->newInstance();
        }



        // Returning the value
        return null;
    }
}



?>
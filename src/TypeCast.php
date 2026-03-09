<?php



namespace Solenoid\MySQL;



use \Attribute;



#[ Attribute( Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE ) ]
class TypeCast
{
    public function __construct (public array $fields) {}



    public static function find (string $model) : static|null
    {
        foreach ( ( new \ReflectionClass( $model ) )->getAttributes( static::class ) as $attribute )
        {// Processing each entry
            // Returning the value
            return $attribute->newInstance();
        }



        // Returning the value
        return null;
    }
}



?>
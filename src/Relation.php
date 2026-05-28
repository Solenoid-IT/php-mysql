<?php



namespace Solenoid\MySQL;



use \Attribute;



#[ Attribute( Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE ) ]
class Relation
{
    public const HAS_ONE    = 'hasOne';
    public const HAS_MANY   = 'hasMany';
    public const BELONGS_TO = 'belongsTo';



    public function __construct
    (
        public string $name,
        public string $model,
        public string $type = self::HAS_MANY,
        public ?string $local_key = null,
        public ?string $foreign_key = null
    )
    {
        if ( $type === self::BELONGS_TO )
        {// Match OK
            // (Getting the values)
            $this->local_key   = $local_key ?? $this->name;
            $this->foreign_key = $foreign_key ?? 'id';
        }
        else
        {// Match failed
            // (Getting the values)
            $this->local_key   = $local_key ?? 'id';
            $this->foreign_key = $foreign_key ?? $this->name;
        }
    }



    public static function resolve (string $src, string $dst) : self|null
    {
        if ( !is_subclass_of( $src, Model::class ) ) return null;
        if ( !is_subclass_of( $dst, Model::class ) ) return null;

    

        foreach ( ( new \ReflectionClass( $src ) )->getAttributes( self::class ) as $attribute )
        {// Processing each entry
            // (Getting the value)
            $args = $attribute->getArguments();



            // (Getting the value)
            $current_dst = $args['model'] ?? $args[1] ?? null;

            if ( $current_dst !== $dst ) continue;



            // Returning the value
            return $attribute->newInstance();
        }



        // Returning the value
        return null;
    }
}



?>
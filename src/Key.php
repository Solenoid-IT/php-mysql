<?php



namespace Solenoid\MySQL;



use \Attribute;



#[ Attribute( Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE ) ]
class Key
{
    public function __construct (public string $name, public array $fields) {}
}



?>
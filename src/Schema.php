<?php



namespace Solenoid\MySQL;



use \Attribute;



#[ Attribute( Attribute::TARGET_CLASS ) ]
class Schema
{
    public function __construct (public string $id_field = 'id', public string $parent_field = 'parent') {}
}



?>
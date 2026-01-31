<?php



namespace Solenoid\MySQL;



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
}



?>
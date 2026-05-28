<?php



namespace App\Models;



use \Solenoid\MySQL\Model;
use \Solenoid\MySQL\Relation;



#[ Relation( 'hierarchy', Hierarchy::class, Relation::BELONGS_TO ) ]
class User extends Model
{
    public string $connection_id = 'local';
    public string $database      = 'db';
    public string $table         = 'user';
}
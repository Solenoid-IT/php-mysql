<?php



namespace App\Models;



use \Solenoid\MySQL\Model;



class Hierarchy extends Model
{
    public string $connection_id = 'local';
    public string $database      = 'db';
    public string $table         = 'hierarchy';
}
<?php



use \Solenoid\MySQL\Model;



/**
 * Helper function to create a new model instance.
 */
function model (string $class) : Model
{
    // Returning the value
    return $class::fetch();
}
<?php
/**
 * Customise Order
 */
class OrderExtension extends DataExtension
{
    /** @var array List of database fields */
    private static $db = array(
        'CustomerNumber' => 'Varchar(255)',
    );
}

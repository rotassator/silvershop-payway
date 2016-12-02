<?php
/**
 * Customise Order
 */
class PaywayOrderExtension extends DataExtension
{
    /** @var array List of database fields */
    private static $db = array(
        'CustomerNumber' => 'Varchar(255)',
    );
}

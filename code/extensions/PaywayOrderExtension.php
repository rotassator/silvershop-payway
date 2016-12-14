<?php
/**
 * Customise Order
 */
class PaywayOrderExtension extends DataExtension
{
    /** @var array List of database fields */
    private static $db = array(
        'CustomerNumber'   => 'Varchar(255)',
        'PaymentFrequency' => "Enum('once, weekly, fortnightly, monthly, quarterly, six-monthly, yearly', 'once')",
        'PaymentDateNext'  => 'Date',
    );
}

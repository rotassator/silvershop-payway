<?php
/**
 * Customise Member
 */
class PaywayMemberExtension extends DataExtension
{
    /** @var array List of database fields */
    private static $db = array(
        'PaywayCustomerNumber' => 'Varchar(255)',
    );
}

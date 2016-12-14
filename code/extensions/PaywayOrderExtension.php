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

    /** @var array Available payment frequencies */
    private static $payment_frequency = array();

    /**
     * Get array of Payment Frequency values
     * @return array Payment frequencies
     */
    public function getPaymentFrequencies()
    {
        return Config::inst()->get('Order', 'payment_frequency');
    }

    /**
     * Get Payment Frequency title
     * @return string Payment frequency title for display
     */
    public function getPaymentFrequencyTitle()
    {
        return ($frequency = $this->owner->PaymentFrequency)
            ? $this->getPaymentFrequencies()[$frequency]
            : null;
    }
}

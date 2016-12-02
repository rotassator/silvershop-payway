<?php
/**
 * Customise SilverShop AccountPage
 */
class PaywayShopAccountFormExtension extends DataExtension
{
    /**
     * Customise Shop Account Form
     * @param  Form $fields Account form
     */
    public function updateShopAccountForm()
    {
        // get fields
        $fields = $this->owner->Fields();
        // remove PayWay customer number from shop account page (auto-generated!)
        $fields->removeByName('PaywayCustomerNumber');
    }
}

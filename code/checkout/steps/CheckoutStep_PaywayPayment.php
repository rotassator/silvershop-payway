<?php
/**
 * Checkout Step for PaywayPayment
 */
class CheckoutStep_PaywayPayment extends CheckoutStep
{
    /** @var array Allowed controller actions */
    private static $allowed_actions = array(
        'paymentdetails',
        'PaymentDetailsForm',
    );

    /**
     * Payment Details step
     * @return array Data for template
     */
    public function paymentdetails()
    {
        // continue if not PayWay
        if (!$this->isPayway()) {
            $this->owner->redirect($this->owner->Link('payment'));
        }

        $form = $this->PaymentDetailsForm();

        return array(
            'OrderForm'     => $form,
            'PaymentMethod' => $this->getPaymentMethod(),
        );
    }

    /**
     * Payment Details form
     * @return PaymentForm Form for step
     */
    public function PaymentDetailsForm()
    {
        $config = new CheckoutComponentConfig(ShoppingCart::curr(), false);
        $this->owner->extend('updatePaymentDetailsComponentConfig', $config);

        $form = PaymentForm::create($this->owner, "PaymentDetailsForm", $config);
        $form->setFailureLink($this->owner->Link('paymentdetails'));

        // remove form actions (next step controlled by JS form)
        $form->unsetAllActions();
        $form->disableDefaultAction();

        $this->owner->extend('updatePaymentDetailsForm', $form);

        return $form;
    }

    /**
     * Get selected payment method
     * @return string [description]
     */
    protected function getPaymentMethod()
    {
        // payment method is stored in the order
        $order = ShoppingCart::curr();
        return Checkout::get($order)->getSelectedPaymentMethod();
    }

    /**
     * Check if a Payway payment method is selected
     * @return boolean True if a Payway method
     */
    protected function isPayway()
    {
        return in_array($this->getPaymentMethod(), array(
            'PaywayRest',
            'PaywayRest_DirectDebit',
        ));
    }

    /**
     * Get config array or parameter
     * @param  string       $parm Config parameter name
     * @return string|array       String if parameter; array if no parameter
     */
    protected function getConfig($parm = null, $gateway = null)
    {
        $gateway = $gateway ?: $this->getPaymentMethod();

        // get PayWay config
        $config = Config::inst()->get('GatewayInfo', $gateway);
        if (!isset($config['parameters'])) {
            user_error("Gateway parameters not found. Should be in GatewayInfo.{$gateway}.parameters");
        }
        // check for specific parameter value
        if ($parm) {
            return (isset($config['parameters'][$parm])) ? $config['parameters'][$parm] : '';
        }
        return $config['parameters'];
    }

    /**
     * Get link to PayWay Credit Card processing form script
     * @return string Link to form script
     */
    public function getPaywayCreditCardJSLink()
    {
        // collect config so it can be extended
        $jsConfig = array(
            'apiLinkCreditCardForm' => $this->getConfig('apiLinkCreditCardForm', 'PaywayRest'),
            'apiKeyPublic'          => $this->getConfig('apiKeyPublic', 'PaywayRest'),
            'returnUrl'             => Director::absoluteURL($this->owner->Link('payment')),
        );

        $this->owner->extend('updatePaywayJSConfig', $jsConfig);

        // check the config
        if (empty($jsConfig['apiLinkCreditCardForm'])) {
            user_error(_t(__CLASS__ . '.MissingApiLinkCCForm', 'Credit Card Form API link was not set - should be in GatewayInfo.PaywayRest.parameters.apiLinkCreditCardForm'));
        }
        if (empty($jsConfig['apiKeyPublic'])) {
            user_error(_t(__CLASS__ . '.MissingApiPublicKey', 'Publishable API key was not set - should be in GatewayInfo.PaywayRest.parameters.apiKeyPublic'));
        }
        if (empty($jsConfig['returnUrl'])) {
            user_error(_t(__CLASS__ . '.MissingReturnUrl', 'Return URL was not set - should be set to the Checkout payment link'));
        }

        // return JS API link
        return join(array(
            $jsConfig['apiLinkCreditCardForm'],
            '?apiKey=',
            $jsConfig['apiKeyPublic'],
            '&amp;redirectUrl=',
            $jsConfig['returnUrl'],
        ));
    }

    /**
     * Get link to PayWay Bank Account processing form script
     * @return string Link to form script
     */
    public function getPaywayBankAccountJSLink()
    {
        // collect config so it can be extended
        $jsConfig = array(
            'apiLinkBankAccountForm' => $this->getConfig('apiLinkBankAccountForm', 'PaywayRest_DirectDebit'),
            'apiKeyPublic'           => $this->getConfig('apiKeyPublic', 'PaywayRest_DirectDebit'),
            'returnUrl'              => Director::absoluteURL($this->owner->Link('payment')),
        );

        $this->owner->extend('updatePaywayBankAccountJSConfig', $jsConfig);

        // check the config
        if (empty($jsConfig['apiLinkBankAccountForm'])) {
            user_error(_t(__CLASS__ . '.MissingApiLinkBAForm', 'Bank Account Form API link was not set - should be in GatewayInfo.PaywayRest_DirectDebit.parameters.apiLinkBankAccountForm'));
        }
        if (empty($jsConfig['apiKeyPublic'])) {
            user_error(_t(__CLASS__ . '.MissingApiBAPublicKey', 'Publishable API key was not set - should be in GatewayInfo.PaywayRest_DirectDebit.parameters.apiKeyPublic'));
        }
        if (empty($jsConfig['returnUrl'])) {
            user_error(_t(__CLASS__ . '.MissingReturnUrl', 'Return URL was not set - should be set to the Checkout payment link'));
        }

        // return JS API link
        return join(array(
            $jsConfig['apiLinkBankAccountForm'],
            '?apiKey=',
            $jsConfig['apiKeyPublic'],
            '&amp;redirectUrl=',
            $jsConfig['returnUrl'],
        ));
    }
}

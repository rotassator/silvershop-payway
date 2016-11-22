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
        $form = $this->PaymentDetailsForm();
        $this->owner->extend('updateConfirmationForm', $form);

        return array(
            'OrderForm' => $form,
        );
    }

    /**
     * Payment Details form
     * @return PaymentForm Form for step
     */
    public function PaymentDetailsForm()
    {
        $config = new CheckoutComponentConfig(ShoppingCart::curr(), false);
        // $config->addComponent(TermsCheckoutComponent::create());
        $this->owner->extend('updatePaymentDetailsComponentConfig', $config);

        $form = PaymentForm::create($this->owner, "PaymentDetailsForm", $config);
        $form->setFailureLink($this->owner->Link('paymentdetails'));
        $this->owner->extend('updatePaymentDetailsForm', $form);

        // remove form actions (next step controlled by JS form)
        $form->unsetAllActions();
        $form->disableDefaultAction();

        return $form;
    }

    /**
     * Get config array or parameter
     * @param  string       $parm Config parameter name
     * @return string|array       String if parameter; array if no parameter
     */
    protected function getConfig($parm = null)
    {
        // get PayWay config
        $config = Config::inst()->get('GatewayInfo', 'PaywayRest');
        if (!isset($config['parameters'])) {
            user_error('Gateway parameters not found. Should be in GatewayInfo.Payway.parameters');
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
            'apiLinkCreditCardForm' => $this->getConfig('apiLinkCreditCardForm'),
            'apiKeyPublic'  => $this->getConfig('apiKeyPublic'),
            'returnUrl'     => Director::absoluteURL($this->owner->Link('payment')),
        );

        $this->owner->extend('updatePaywayJSConfig', $jsConfig);

        // check the config
        if (empty($jsConfig['apiLinkCreditCardForm'])) {
            user_error(_t(__CLASS__ . '.MissingApiLinkCCForm', 'Credit Card Form API link was not set - should be in GatewayInfo.Payway.parameters.apiLinkCreditCardForm'));
        }
        if (empty($jsConfig['apiKeyPublic'])) {
            user_error(_t(__CLASS__ . '.MissingApiPublicKey', 'Publishable API key was not set - should be in GatewayInfo.Payway.parameters.apiKeyPublic'));
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
}
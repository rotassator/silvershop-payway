<?php
/** PaywayPaymentCheckoutComponent */
use SilverStripe\Omnipay\Service\PurchaseService;

/**
 * This replaces the default OnsitePaymentCheckoutComponent and uses the PayWay
 * javascript to return a token to interact with the REST API.
 *
 * @see https://www.payway.com.au/rest-docs/index.html
 *
 * By default this replaces OnsitePaymentCheckoutComponent via the injector in
 * either single page or multi-step checkout.
 *
 * @package silvershop-payway
 */
class PaywayPaymentCheckoutComponent extends OnsitePaymentCheckoutComponent
{
    /** @var bool - if for some reason the gateway is not actually payway, fall back to OnsitePayment */
    protected $isPayway;

    /** @var \Omnipay\Common\AbstractGateway|\Omnipay\PaywayRest\Gateway */
    protected $gateway;

    /**
     * Get gateway
     *
     * @param Order $order
     * @return \Omnipay\Common\AbstractGateway|\Omnipay\PaywayRest\Gateway
     */
    protected function getGateway($order)
    {
        if (!isset($this->gateway)) {
            $tempPayment = new Payment(array(
                'Gateway' => Checkout::get($order)->getSelectedPaymentMethod(false),
            ));
            $service = PurchaseService::create($tempPayment);
            $this->setGateway($service->oGateway());
        }

        return $this->gateway;
    }

    /**
     * Set gateway
     *
     * @param \Omnipay\Common\AbstractGateway|\Omnipay\PaywayRest\Gateway $gateway
     * @return $this
     */
    public function setGateway($gateway)
    {
        $this->gateway = $gateway;
        $this->isPayway = ($this->gateway instanceof \Omnipay\PaywayRest\Gateway);
        return $this;
    }

    /**
     * Get form fields for manipulating the current order,
     * according to the responsibility of this component.
     *
     * @param Order $order
     * @param Form $form
     * @return FieldList
     */
    public function getFormFields(Order $order, Form $form = null)
    {
        $gateway = $this->getGateway($order);
        if (!$this->isPayway) {
            return parent::getFormFields($order);
        }

        // create list of fields
        $fields = FieldList::create(array(
            HiddenField::create("singleUseTokenId", "singleUseTokenId", $this->getToken()),
        ));

        $this->extend('updateFormFields', $fields);

        return $fields;
    }

    /**
     * Get the data fields that are required for the component.
     *
     * @param  Order $order [description]
     * @return array        required data fields
     */
    public function getRequiredFields(Order $order)
    {
        $this->getGateway($order);
        if (!$this->isPayway) {
            return parent::getRequiredFields($order);
        }
        return array();
    }

    /**
     * Is this data valid for saving into an order?
     *
     * This function should never rely on form.
     *
     * @param Order $order
     * @param array $data data to be validated
     *
     * @throws ValidationException
     * @return boolean the data is valid
     */
    public function validateData(Order $order, array $data)
    {
        $this->getGateway($order);
        if (!$this->isPayway) {
            return parent::validateData($order, $data);
        }
        // PayWay performs its own validation
        return true;
    }

    /**
     * Get required data out of the model.
     *
     * @param  Order $order order to get data from.
     * @return array        get data from model(s)
     */
    public function getData(Order $order)
    {
        $this->getGateway($order);
        if (!$this->isPayway) {
            return parent::getData($order);
        }
        return array();
    }

    /**
     * Set the model data for this component.
     *
     * This function should never rely on form.
     *
     * @param Order $order
     * @param array $data data to be saved into order object
     *
     * @throws Exception
     * @return Order the updated order
     */
    public function setData(Order $order, array $data)
    {
        $this->getGateway($order);
        if (!$this->isPayway) {
            return parent::setData($order, $data);
        }
        return array();
    }

    /**
     * Get Token from the URL parameters
     * @return string Single-use token passed from PayWay API
     */
    public function getToken()
    {
        return Controller::curr()->getRequest()->getVar('singleUseTokenId');
    }
}

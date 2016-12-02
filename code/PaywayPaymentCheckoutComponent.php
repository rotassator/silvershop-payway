<?php
/** PaywayPaymentCheckoutComponent */
use SilverStripe\Omnipay\Service\PurchaseService;

/**
 * This replaces the default OnsitePaymentCheckoutComponent and uses the token returned by
 * the PayWay javascript to interact with the REST API.
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
            HiddenField::create("singleUseTokenId", "singleUseTokenId", $this->getSingleUseTokenId()),
            HiddenField::create('customerNumber', 'customerNumber', $this->getCustomerNumber($order)),
            HiddenField::create('principalAmount', 'principalAmount', $order->Total()),
            HiddenField::create('currency', 'currency', $this->getCurrency($order)),
            HiddenField::create('orderNumber', 'orderNumber', $order->Reference),
            HiddenField::create('merchantId', 'merchantId', $this->getMerchantId()),
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
     * @param Order $order
     * @param array $data data to be validated
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
     * Get Customer number
     * @return string Customer number as supplied by PayWay
     */
    public function getCustomerNumber(Order $order)
    {
        // check if number already against order
        if ($order->CustomerNumber) {
            return $order->CustomerNumber;
        }
        // check for existing member
        $member = $order->Member();
        if ($member->exists() && $member->PaywayCustomerNumber) {
            return $member->PaywayCustomerNumber;
        }

        // set up gateway
        $this->getGateway($order);
        // ensure PayWay
        if (!$this->isPayway) {
            return null;
        }

        // create new customer
        $response = $this->createCustomer();

        /** @todo handle this more gracefully */
        if (!$response->isSuccessful()) {
            return null;
        }

        // update order
        $order->CustomerNumber = $response->getCustomerNumber();
        $order->write();

        // update member
        if ($member->exists()) {
            $member->PaywayCustomerNumber = $response->getCustomerNumber();
            $member->write();
        }

        // update customer contact details
        $contactResponse = $this->updateCustomerContact($order);

        return $order->CustomerNumber;
    }

    /**
     * Generate new customer
     * @return Response Response object
     */
    public function createCustomer()
    {
        // create new customer
        return $this->gateway->createCustomer(array(
            'singleUseTokenId' => $this->getSingleUseTokenId(),
            'merchantId'       => $this->getMerchantId(),
        ))->send();
    }

    /**
     * Update customer contact details
     * @return Response Response object
     */
    public function updateCustomerContact($order)
    {
        // gather details
        $data = array(
            'customerNumber' => $order->CustomerNumber,
            'customerName' => $order->getName(),
            'emailAddress' => $order->Email,
        );
        // add billing address
        $address = $order->BillingAddress();
        if ($address->exists() && $address->Country === 'AU') { // PayWay only appears to support Australian addresses
            $data = array_merge($data, array(
                'phoneNumber' => $address->Phone,
                'street1'     => $address->Address,
                'street2'     => $address->AddressLine2,
                'cityName'    => $address->City,
                'state'       => $address->State,
                'postalCode'  => $address->PostalCode,
            ));
        }

        // update contact details
        return $this->gateway->updateCustomerContact($data)->send();
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
        $data = array(
            'customerNumber'  => $this->getCustomerNumber($order),
            'principalAmount' => $order->Total(),
            'currency'        => $this->getCurrency($order),
            'orderNumber'     => $order->Reference,
            'merchantId'      => $this->getMerchantId(),
        );

        return $data;
    }

    /**
     * Set the model data for this component.
     *
     * @param Order $order
     * @param array $data data to be saved into order object
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

    public function getParameters()
    {
        return new ParameterBag;
    }

    public function getParameter($key)
    {
        return $this->getParameters()->get($key);
    }

    /**
     * Get Token from the URL parameters
     * @return string Single-use token passed from PayWay API
     */
    public function getSingleUseTokenId()
    {
        return Controller::curr()->getRequest()->requestVar('singleUseTokenId');
    }

    /**
     * Get config array or parameter
     * @param  string       $parm Config parameter name
     * @return string|array       String if parameter; array if no parameter
     */
    protected function getMerchantId()
    {
        // get PayWay config
        $config = Config::inst()->get('GatewayInfo', 'PaywayRest');
        if (isset($config['parameters'])) {
            return (isset($config['parameters']['merchantId'])) ? $config['parameters']['merchantId'] : '';
        }
    }

    public function getCurrency(Order $order)
    {
        return isset($order) ? strtolower($order->Currency()) : null;
    }
}

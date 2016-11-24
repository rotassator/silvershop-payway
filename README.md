# SilverShop PayWay Support Module

Westpac PayWay payment integration for SilverShop.

PayWay uses client-side JavaScript to generate tokens for using with their REST API instead of interacting with credit card details directly on your site.

To integrate the Omnipay PaywayRest adapter with SilverShop, this module overrides the default checkout component and injects the required JavaScript.

## Kudos

Many thanks to the work done by Mark Guinn on the [Braintree](https://github.com/markguinn/silvershop-braintree) and [Stripe](https://github.com/markguinn/silvershop-stripe) modules.

## Installation

Create a config file (eg. `mysite/_config/payment.yml`) similar to the following:

```
---
Name: payment
---
Payment:
  allowed_gateways:
    - 'PaywayRest'

---
Except:
  environment: 'live'
---
GatewayInfo:
  PaywayRest:
    parameters:
      apikey_public: PUBLISHABLE-KEY-FOR-TEST-ACCOUNT
      apikey_secret: SECRET-KEY-FOR-YOUR-TEST-ACCOUNT
      merchant: TEST
      testMode: true

---
Only:
  environment: 'live'
---
GatewayInfo:
  PaywayRest:
    parameters:
      apikey_public: PUBLISHABLE-KEY-FOR-LIVE-ACCOUNT
      apikey_secret: SECRET-KEY-FOR-YOUR-LIVE-ACCOUNT
      merchant: MERCHANT-ID
```

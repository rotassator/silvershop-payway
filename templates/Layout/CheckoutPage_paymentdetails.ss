<% require themedCSS(checkout,shop) %>
<% if $PaymentErrorMessage %>
    <p class="message error">
        <%t CheckoutPage.PaymentErrorMessage 'Received error from payment gateway:' %>
        $PaymentErrorMessage
    </p>
<% end_if %>

<h1>$Title</h1>

<% if $IsPastStep('paymentmethod') %>
    <h3><a class="accordion-toggle" title="choose payment method" href="$Link('paymentmethod')">
        <%t OrderActionsForm.PaymentMethod "Payment Method" %>
    </a></h3>
<% end_if %>

<div class="paywaycreditcard">
    <script type="text/javascript" src="$PaywayCreditCardJSLink"></script>
    <button id="payway-creditcard-submit" onclick="paywayCreditCardSubmit()" disabled="disabled"><%t CheckoutStep_PaywayPayment.CreditCardFormButtonText 'Use Credit Card' %></button>
</div>

$OrderForm


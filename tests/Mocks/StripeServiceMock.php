<?php

namespace Tests\Mocks;

class StripeServiceMock
{
    public function createCustomer($email, $name = null)
    {
        return [
            'id' => 'cus_' . uniqid(),
            'email' => $email,
            'name' => $name,
            'created' => time()
        ];
    }
    
    public function createPaymentIntent($amount, $currency = 'eur')
    {
        return [
            'id' => 'pi_' . uniqid(),
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'succeeded',
            'client_secret' => 'pi_secret_' . uniqid()
        ];
    }
    
    public function createInvoice($customerId, $items)
    {
        return [
            'id' => 'inv_' . uniqid(),
            'customer' => $customerId,
            'amount_due' => array_sum(array_column($items, 'amount')),
            'status' => 'paid',
            'pdf' => 'https://example.com/invoice.pdf'
        ];
    }
}
<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']); // Set API key directly from env
    }

    public function createCheckoutSession(array $products, int $commandeId): ?string
    {
        try {
            $lineItems = [];

            foreach ($products as $product) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd', // Change if needed (e.g., "eur")
                        'unit_amount' => $product['amount'], // Price in cents
                        'product_data' => [
                            'name' => $product['name'],
                        ],
                    ],
                    'quantity' => $product['quantity'],
                ];
            }

            $session = Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'success_url' => 'https://yourdomain.com/payment/success/{CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://yourdomain.com/payment/cancel',
                'line_items' => $lineItems,
                'metadata' => [
                    'commande_id' => $commandeId
                ],
            ]);

            return $session->url;
        } catch (ApiErrorException $e) {
            return null;
        }
    }
}

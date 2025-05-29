<?php

namespace App\Controller;

use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    #[Route('/create-session', name: 'payment_create_session', methods: ['GET'])]
    public function createSession(PaymentService $paymentService): JsonResponse
    {
        $sessionUrl = $paymentService->createCheckoutSession(10.00); // Example: $10.00

        if (!$sessionUrl) {
            return new JsonResponse(['error' => 'Payment session creation failed'], 500);
        }

        return new JsonResponse(['url' => $sessionUrl]);
    }
}

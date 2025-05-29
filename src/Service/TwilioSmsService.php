<?php
namespace App\Service;

use Twilio\Rest\Client;

class TwilioSmsService
{
    private Client $twilioClient;
    private string $twilioPhoneNumber;

    public function __construct(string $twilioSid, string $twilioAuthToken, string $twilioPhoneNumber)
    {
        $this->twilioPhoneNumber = $twilioPhoneNumber;
        $this->twilioClient = new Client($twilioSid, $twilioAuthToken);
    }

    public function sendSms(string $to, string $message): void
    {
        try {
            $this->twilioClient->messages->create(
                $to,
                [
                    'from' => $this->twilioPhoneNumber,
                    'body' => $message,
                ]
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Twilio SMS failed: ' . $e->getMessage());
        }
    }
}

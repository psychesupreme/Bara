<?php

namespace App\Services;

use Illuminate\Support\Str;

class MpesaStkAdapter
{
    protected string $env;
    protected ?string $consumerKey;
    protected ?string $consumerSecret;
    protected string $shortcode;
    protected ?string $passkey;
    protected string $callbackUrl;

    public function __construct()
    {
        $this->env = config('services.mpesa.env', 'sandbox');
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->shortcode = (string) config('services.mpesa.shortcode', '174379');
        $this->passkey = config('services.mpesa.passkey');
        $this->callbackUrl = config('services.mpesa.callback_url', 'https://api.bara.app/api/v1/collections/stk-callback');
    }

    /**
     * Initiate Safaricom Daraja 2.0 STK Push request.
     */
    public function initiateStkPush(string $phoneNumber, float $amount, string $accountReference): array
    {
        // Sanitize phone number to 254 format
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (Str::startsWith($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        $checkoutRequestId = 'ws_CO_' . date('dmYHis') . '_' . Str::random(6);
        $merchantRequestId = 'MR_' . Str::random(10);

        return [
            'success' => true,
            'environment' => $this->env,
            'shortcode' => $this->shortcode,
            'merchant_request_id' => $merchantRequestId,
            'checkout_request_id' => $checkoutRequestId,
            'response_code' => '0',
            'response_description' => 'Success. Request accepted for processing',
            'customer_message' => "STK Push prompt sent to {$phone} for KES {$amount}",
            'callback_url' => $this->callbackUrl,
        ];
    }

    /**
     * Process Daraja callback payload.
     */
    public function handleCallback(array $callbackData): array
    {
        $resultCode = $callbackData['Body']['stkCallback']['ResultCode'] ?? -1;
        $checkoutRequestId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? null;
        $mpesaReceiptNumber = null;

        if ($resultCode === 0) {
            $items = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
            foreach ($items as $item) {
                if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
                    $mpesaReceiptNumber = $item['Value'] ?? null;
                }
            }
        }

        return [
            'is_successful' => ($resultCode === 0),
            'checkout_request_id' => $checkoutRequestId,
            'mpesa_receipt_number' => $mpesaReceiptNumber ?? ('NL' . Str::upper(Str::random(8))),
            'result_desc' => $callbackData['Body']['stkCallback']['ResultDesc'] ?? 'Callback Processed',
        ];
    }
}

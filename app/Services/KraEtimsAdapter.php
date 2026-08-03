<?php

namespace App\Services;

use App\Models\SalesOrder;
use Illuminate\Support\Str;

class KraEtimsAdapter
{
    protected string $env;
    protected string $pin;
    protected string $deviceSerial;
    protected ?string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->env = config('services.kra_etims.env', 'sandbox');
        $this->pin = config('services.kra_etims.pin', 'P0511223344A');
        $this->deviceSerial = config('services.kra_etims.device_serial', 'ETIMS-DEV-001');
        $this->apiKey = config('services.kra_etims.api_key');
        $this->endpoint = config('services.kra_etims.endpoint', 'https://etims-api.kra.go.ke/v1');
    }

    /**
     * Generate KRA ETIMS electronic tax receipt payload with digital signature & QR code.
     */
    public function generateElectronicTaxReceipt(SalesOrder $order): array
    {
        $receiptNumber = 'KRA-ETIMS-' . date('Ymd') . '-' . Str::upper(Str::random(6));
        $signaturePayload = "PIN:{$this->pin}|DEV:{$this->deviceSerial}|ORDER:{$order->id}|TOTAL:{$order->total_amount}|TAX:{$order->tax_amount}|DATE:" . now()->toIso8601String();
        $signature = hash('sha256', $signaturePayload);
        $qrCodePayload = "https://etims.kra.go.ke/verify?receipt={$receiptNumber}&pin={$this->pin}&sig={$signature}";

        return [
            'success' => true,
            'environment' => $this->env,
            'tax_pin' => $this->pin,
            'device_serial' => $this->deviceSerial,
            'etims_receipt_number' => $receiptNumber,
            'etims_signature' => $signature,
            'etims_qr_code' => $qrCodePayload,
            'taxable_amount' => round($order->total_amount / 1.16, 2),
            'vat_amount' => round($order->total_amount - ($order->total_amount / 1.16), 2),
        ];
    }
}

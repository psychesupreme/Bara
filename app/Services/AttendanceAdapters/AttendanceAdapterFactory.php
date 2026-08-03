<?php

namespace App\Services\AttendanceAdapters;

use App\Services\VerificationResultEngine;
use InvalidArgumentException;

class AttendanceAdapterFactory
{
    public function __construct(
        protected VerificationResultEngine $verificationEngine
    ) {}

    public function make(string $adapterType): AttendanceAdapterInterface
    {
        return match ($adapterType) {
            'gps' => new GpsAttendanceAdapter($this->verificationEngine),
            'qr_code' => new QrCodeAttendanceAdapter(),
            'face_id' => new FaceIdAttendanceAdapter(),
            default => new GpsAttendanceAdapter($this->verificationEngine),
        };
    }
}

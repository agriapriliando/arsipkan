<?php

namespace App\Services;

class PhoneNumberNormalizer
{
    public function normalize(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '62')) {
            $normalized = $digits;
        } elseif (str_starts_with($digits, '0')) {
            $normalized = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $normalized = '62'.$digits;
        } else {
            return '';
        }

        return preg_match('/^628\d{7,13}$/', $normalized) === 1
            ? $normalized
            : '';
    }

    public function isValid(string $phoneNumber): bool
    {
        return $this->normalize($phoneNumber) !== '';
    }
}

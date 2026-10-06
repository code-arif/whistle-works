<?php

use App\Models\Setting;

if (!function_exists('getFileName')) {
    /**
     * Generate a unique timestamp-prefixed filename from an uploaded file.
     *
     * @param  \Illuminate\Http\UploadedFile|mixed  $file
     * @return string
     */
    function getFileName($file): string
    {
        return time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    }
}

if (!function_exists('getEmailName')) {
    /**
     * Extract the username/prefix part of an email address before the '@' sign.
     *
     * @param  string  $email
     * @return string
     */
    function getEmailName($email): string
    {
        $parts = explode('@', $email);
        return $parts[0];
    }
}

if (!function_exists('formatNumber')) {
    /**
     * Format large numbers into human-readable compact notations (K, M, B, T, Q).
     *
     * @param  float|int  $number
     * @param  int        $precision
     * @return array{number: string, format: string}
     */
    function formatNumber($number, $precision = 2): array
    {
        if ($number >= 1000000000000000) {
            return [
                'number' => number_format($number / 1000000000000000, $precision),
                'format' => 'Q',
            ];
        } elseif ($number >= 1000000000000) {
            return [
                'number' => number_format($number / 1000000000000, $precision),
                'format' => 'T',
            ];
        } elseif ($number >= 1000000000) {
            return [
                'number' => number_format($number / 1000000000, $precision),
                'format' => 'B',
            ];
        } elseif ($number >= 1000000) {
            return [
                'number' => number_format($number / 1000000, $precision),
                'format' => 'M',
            ];
        } elseif ($number >= 1000) {
            return [
                'number' => number_format($number / 1000, $precision),
                'format' => 'K',
            ];
        }

        // For numbers less than 1K, no format suffix is needed
        return [
            'number' => number_format($number),
            'format' => '',
        ];
    }
}

if (!function_exists('is_url')) {
    /**
     * Validate whether a given string is a valid URL.
     *
     * @param  mixed  $url
     * @return bool
     */
    function is_url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('settings')) {
    /**
     * Retrieve global system settings model or a specific setting attribute.
     *
     * @param  string|null  $key
     * @return mixed|\App\Models\Setting|null
     */
    function settings(?string $key = null)
    {
        $settings = Setting::first();
        if ($key) {
            return $settings->{$key} ?? null;
        }
        return $settings;
    }
}

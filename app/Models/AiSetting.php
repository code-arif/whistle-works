<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AiSetting extends Model
{
    use HasFactory;

    protected $table = 'ai_settings';

    protected $fillable = [
        'key_name',
        'key_value',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get a setting value by key name.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key_name', $key)->first();
        if (!$setting) {
            return $default;
        }

        if ($setting->is_encrypted && !empty($setting->key_value)) {
            try {
                return Crypt::decryptString($setting->key_value);
            } catch (\Exception $e) {
                return $setting->key_value;
            }
        }

        return $setting->key_value ?? $default;
    }

    /**
     * Set a setting value by key name.
     */
    public static function setValue(string $key, mixed $value, bool $encrypt = false): static
    {
        $storedValue = $value;
        if ($encrypt && !empty($value)) {
            $storedValue = Crypt::encryptString($value);
        }

        return static::updateOrCreate(
            ['key_name' => $key],
            [
                'key_value' => $storedValue,
                'is_encrypted' => $encrypt,
            ]
        );
    }
}

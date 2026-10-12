<?php

namespace App\Rules;

use App\Services\UrlGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeTargetUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(UrlGuard::class)->isSafe($value)) {
            $fail('Cette adresse cible n\'est pas autorisée (http ou https vers une adresse publique uniquement).');
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OneTimePassword extends Model
{
    protected $fillable = [
        'email', 'purpose', 'code_hash', 'attempts', 'expires_at', 'verified_at',
        'verification_token_hash', 'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = [
        'admin_id', 'staff_id', 'customer_id', 'type', 'title', 'message', 'entity_type', 'entity_id', 'data', 'read_at',
    ];

    protected $appends = ['is_read'];

    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime'];
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }
}

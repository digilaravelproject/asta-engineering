<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentCertificate extends Model
{
    protected $fillable = [
        'equipment_id', 'certificate_number', 'title', 'file_path', 'issued_at', 'expires_at',
    ];

    protected $appends = ['file_url'];

    protected function casts(): array
    {
        return ['issued_at' => 'date:Y-m-d', 'expires_at' => 'date:Y-m-d'];
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
    }
}

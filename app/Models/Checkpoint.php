<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'checkpoint_code',
        'name',
        'equipment_category',
        'input_type',
        'is_mandatory',
        'requires_failure_photo',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'requires_failure_photo' => 'boolean',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

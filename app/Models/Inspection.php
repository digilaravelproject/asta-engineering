<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    protected $fillable = [
        'admin_id', 'inspector_id', 'site_id', 'equipment_id', 'scheduled_at',
        'status', 'completed_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function inspector()
    {
        return $this->belongsTo(Staff::class, 'inspector_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}

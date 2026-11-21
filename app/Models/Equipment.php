<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipments';

    protected $fillable = [
        'site_id', 'name', 'short_name', 'manufacturer', 'model', 'serial_number',
        'equipment_code', 'rated_capacity', 'swl_capacity', 'description', 'remarks',
        'next_inspection_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'next_inspection_at' => 'date:Y-m-d'];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function certificates()
    {
        return $this->hasMany(EquipmentCertificate::class);
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }
}

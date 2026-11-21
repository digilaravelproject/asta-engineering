<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        'customer_id', 'site_name', 'flat_door_block', 'building_premises',
        'road_street_lane', 'area_locality', 'pincode', 'email', 'phone_number',
        'mobile_number', 'additional_contact_details', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }
}

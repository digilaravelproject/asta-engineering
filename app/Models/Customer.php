<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'admin_id', 'company_name', 'contact_person_name', 'email', 'mobile_number',
        'password', 'gst_number', 'billing_address', 'pincode', 'logo_path', 'is_active',
    ];

    protected $hidden = ['password'];

    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }
}

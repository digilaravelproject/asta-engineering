<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'admin_id', 'full_name', 'email', 'mobile_number', 'employee_id', 'role',
        'password', 'profile_image_path', 'is_active',
    ];

    protected $hidden = ['password'];

    protected $appends = ['profile_image_url'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class, 'inspector_id');
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->profile_image_path ? asset('storage/'.$this->profile_image_path) : null;
    }
}

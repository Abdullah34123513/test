<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    protected $fillable = [
        'user_id',
        'battery_level',
        'is_charging',
        'latitude',
        'longitude',
        'network_type',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

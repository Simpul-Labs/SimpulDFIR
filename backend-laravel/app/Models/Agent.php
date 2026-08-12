<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Agent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'hostname',
        'ip_address',
        'auth_token',
        'is_online',
        'last_seen'
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_seen' => 'datetime',
    ];

    public function liveLogs()
    {
        return $this->hasMany(LiveLog::class);
    }
}

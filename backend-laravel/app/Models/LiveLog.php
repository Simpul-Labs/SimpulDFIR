<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LiveLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'agent_id',
        'timestamp',
        'source_ip',
        'log_message',
        'threat_level'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}

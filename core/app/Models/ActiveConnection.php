<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'proto',
        'local_address',
        'state',
        'pid_program',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}

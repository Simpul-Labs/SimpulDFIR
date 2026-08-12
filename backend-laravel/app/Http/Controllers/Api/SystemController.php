<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SystemController extends Controller
{
    public function status()
    {
        // Equivalent to the Python system status endpoint
        return response()->json([
            'ntp_sync' => true,
            'system_time' => Carbon::now()->toIso8601String()
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiteVisitStub extends Model
{
    protected $table = 'visits';

    public function recordVisit(Request $request): void
    {
        $sessionId = $request->session()->getId();
        $ip = $request->ip();
        $primaryKey = "visits:sitevisitstubs_visits_recorded_ips:{$ip}";

        Log::info('Recording visit', [
            'primary_key' => $primaryKey,
            'session_id' => $sessionId,
            'created_at' => Carbon::now()->toDateTimeString(),
        ]);

        $existingSession = DB::table('visits')
            ->where('primary_key', $primaryKey)
            ->where('session_id', $sessionId)
            ->whereRaw("CONVERT_TZ(created_at, '+00:00', '+07:00') > ?", [
                Carbon::now('Asia/Ho_Chi_Minh')->subMinutes(30),
            ])
            ->exists();

        if (!$existingSession) {
            DB::table('visits')->insert([
                'primary_key' => $primaryKey,
                'session_id' => $sessionId,
                'score' => 0, // Thêm giá trị cho cột score
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}

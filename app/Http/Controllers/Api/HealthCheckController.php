<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    /**
     * API Health Check endpoint
     * GET /api/health
     */
    public function check(): JsonResponse
    {
        try {
            // Vérifier la connexion à la base de données
            $dbStatus = $this->checkDatabase();
            
            // Vérifier les services essentiels
            $services = [
                'database' => $dbStatus,
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
            ];
            
            $allHealthy = collect($services)->every(fn($status) => $status === 'ok');
            
            return response()->json([
                'status' => $allHealthy ? 'healthy' : 'degraded',
                'timestamp' => now()->toISOString(),
                'services' => $services,
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
            ], $allHealthy ? 200 : 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'timestamp' => now()->toISOString(),
                'message' => 'Health check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simple ping endpoint for connectivity test
     * GET /api/ping
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'message' => 'pong',
            'timestamp' => now()->toISOString(),
            'server_time' => time(),
        ]);
    }

    /**
     * Get server info for debugging
     * GET /api/server-info
     */
    public function serverInfo(): JsonResponse
    {
        return response()->json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time' => now()->toISOString(),
            'timezone' => config('app.timezone'),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            DB::connection()->select('SELECT 1');
            return 'ok';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    private function checkCache(): string
    {
        try {
            cache()->put('health_check', 'test', 10);
            $value = cache()->get('health_check');
            cache()->forget('health_check');
            
            return $value === 'test' ? 'ok' : 'error';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    private function checkStorage(): string
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            \Storage::put($testFile, 'test');
            $content = \Storage::get($testFile);
            \Storage::delete($testFile);
            
            return $content === 'test' ? 'ok' : 'error';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}


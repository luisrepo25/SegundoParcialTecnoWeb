<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$times = [];
$start = microtime(true);

// Test 1: Simple Database Query
try {
    $user = \App\Models\Usuario::first();
    $times['DB First User'] = microtime(true) - $start;
} catch (\Exception $e) {
    $times['DB Error'] = $e->getMessage();
}

// Test 2: N+1 or relationship loading latency
$start2 = microtime(true);
$users = \App\Models\Usuario::with('rol')->limit(50)->get();
$times['DB 50 Users + Roles'] = microtime(true) - $start2;

// Test 3: JWT Hash Check
$start3 = microtime(true);
\Illuminate\Support\Facades\Hash::make('password123');
$times['Hash::make'] = microtime(true) - $start3;

// Output
echo "=== LATENCY TEST RESULTS ===\n";
foreach ($times as $key => $time) {
    if (is_numeric($time)) {
        echo str_pad($key, 25) . ": " . number_format($time, 4) . " seconds\n";
    } else {
        echo str_pad($key, 25) . ": " . $time . "\n";
    }
}

<?php
/**
 * PHP Application Metrics Exporter for Prometheus
 * Collects basic PHP and system metrics
 */

header('Content-Type: text/plain; charset=utf-8');

// Basic PHP metrics
echo "# HELP php_info PHP version info\n";
echo "# TYPE php_info gauge\n";
echo "php_info{version=\"" . PHP_VERSION . "\"} 1\n";

echo "# HELP php_memory_usage_bytes Current memory usage in bytes\n";
echo "# TYPE php_memory_usage_bytes gauge\n";
echo "php_memory_usage_bytes " . memory_get_usage(true) . "\n";

echo "# HELP php_memory_peak_usage_bytes Peak memory usage in bytes\n";
echo "# TYPE php_memory_peak_usage_bytes gauge\n";
echo "php_memory_peak_usage_bytes " . memory_get_peak_usage(true) . "\n";

// System load (if available)
if (function_exists('sys_getloadavg')) {
    $load = sys_getloadavg();
    echo "# HELP system_load1 1-minute system load average\n";
    echo "# TYPE system_load1 gauge\n";
    echo "system_load1 $load[0]\n";

    echo "# HELP system_load5 5-minute system load average\n";
    echo "# TYPE system_load5 gauge\n";
    echo "system_load5 $load[1]\n";

    echo "# HELP system_load15 15-minute system load average\n";
    echo "# TYPE system_load15 gauge\n";
    echo "system_load15 $load[2]\n";
}

// Disk usage
$disk_free = disk_free_space('/');
$disk_total = disk_total_space('/');
$disk_used = $disk_total - $disk_free;

echo "# HELP disk_free_bytes Free disk space in bytes\n";
echo "# TYPE disk_free_bytes gauge\n";
echo "disk_free_bytes $disk_free\n";

echo "# HELP disk_total_bytes Total disk space in bytes\n";
echo "# TYPE disk_total_bytes gauge\n";
echo "disk_total_bytes $disk_total\n";

echo "# HELP disk_used_bytes Used disk space in bytes\n";
echo "# TYPE disk_used_bytes gauge\n";
echo "disk_used_bytes $disk_used\n";

echo "# HELP disk_usage_percent Disk usage percentage\n";
echo "# TYPE disk_usage_percent gauge\n";
echo "disk_usage_percent " . round(($disk_used / $disk_total) * 100, 2) . "\n";

// Request count (using a simple file-based counter)
$counter_file = __DIR__ . '/metrics_counter.txt';
if (!file_exists($counter_file)) {
    file_put_contents($counter_file, '0');
}
$counter = (int)file_get_contents($counter_file);
$counter++;
file_put_contents($counter_file, $counter);

echo "# HELP http_requests_total Total number of HTTP requests\n";
echo "# TYPE http_requests_total counter\n";
echo "http_requests_total $counter\n";

// Response time (simulated)
$response_time = mt_rand(50, 500); // Random response time for demo
echo "# HELP http_request_duration_milliseconds HTTP request duration in milliseconds\n";
echo "# TYPE http_request_duration_milliseconds histogram\n";
echo "http_request_duration_milliseconds_bucket{le=\"100\"} " . ($response_time <= 100 ? 1 : 0) . "\n";
echo "http_request_duration_milliseconds_bucket{le=\"200\"} " . ($response_time <= 200 ? 1 : 0) . "\n";
echo "http_request_duration_milliseconds_bucket{le=\"500\"} " . ($response_time <= 500 ? 1 : 0) . "\n";
echo "http_request_duration_milliseconds_bucket{le=\"+Inf\"} 1\n";
echo "http_request_duration_milliseconds_count 1\n";
echo "http_request_duration_milliseconds_sum $response_time\n";
?>
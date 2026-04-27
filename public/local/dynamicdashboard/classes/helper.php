<?php
namespace local_dynamicdashboard;

class helper {
    public static function relative_time(int $timestamp): string {
        $diff = time() - $timestamp;
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff/60) . ' min ago';
        if ($diff < 86400) return floor($diff/3600) . ' hrs ago';
        return date('M j', $timestamp);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupMonitorController extends Controller
{
    private $backupDir = '/var/backups/askproai';
    
    /**
     * Display the backup monitoring dashboard
     */
    public function index()
    {
        $data = [
            'status' => $this->getSystemStatus(),
            'backups' => $this->getRecentBackups(),
            'metrics' => $this->getMetrics(),
            'issues' => $this->getRecentIssues(),
            'lastUpdate' => Carbon::now()->format('Y-m-d H:i:s')
        ];
        
        return view('admin.backup-monitor', $data);
    }
    
    /**
     * Get backup system status via API
     */
    public function status()
    {
        return response()->json([
            'status' => $this->getSystemStatus(),
            'metrics' => $this->getMetrics(),
            'lastBackup' => $this->getLastBackupTime(),
            'diskUsage' => $this->getDiskUsage(),
            'timestamp' => now()->toIso8601String()
        ]);
    }
    
    /**
     * Get recent backups list
     */
    private function getRecentBackups()
    {
        $backups = [];
        
        // Database backups
        $dbBackups = glob($this->backupDir . '/db/*.sql.gz');
        foreach ($dbBackups as $backup) {
            $backups[] = [
                'type' => 'database',
                'file' => basename($backup),
                'size' => $this->formatBytes(filesize($backup)),
                'age' => $this->getFileAge($backup),
                'time' => date('Y-m-d H:i:s', filemtime($backup))
            ];
        }
        
        // File backups
        $fileBackups = glob($this->backupDir . '/files/*.tar.gz');
        foreach ($fileBackups as $backup) {
            $backups[] = [
                'type' => 'files',
                'file' => basename($backup),
                'size' => $this->formatBytes(filesize($backup)),
                'age' => $this->getFileAge($backup),
                'time' => date('Y-m-d H:i:s', filemtime($backup))
            ];
        }
        
        // Sort by time descending
        usort($backups, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($backups, 0, 10); // Return last 10
    }
    
    /**
     * Get system metrics
     */
    private function getMetrics()
    {
        $metrics = [
            'totalBackups' => 0,
            'totalSize' => 0,
            'oldestBackup' => null,
            'averageSize' => 0,
            'backupRate' => 0
        ];
        
        $allBackups = array_merge(
            glob($this->backupDir . '/db/*.sql.gz'),
            glob($this->backupDir . '/files/*.tar.gz')
        );
        
        $metrics['totalBackups'] = count($allBackups);
        
        if ($metrics['totalBackups'] > 0) {
            $sizes = array_map('filesize', $allBackups);
            $metrics['totalSize'] = $this->formatBytes(array_sum($sizes));
            $metrics['averageSize'] = $this->formatBytes(array_sum($sizes) / count($sizes));
            
            $times = array_map('filemtime', $allBackups);
            $metrics['oldestBackup'] = date('Y-m-d', min($times));
            
            // Calculate backup rate (backups per day)
            $daysSinceOldest = (time() - min($times)) / 86400;
            $metrics['backupRate'] = $daysSinceOldest > 0 
                ? round($metrics['totalBackups'] / $daysSinceOldest, 2) 
                : 0;
        }
        
        return $metrics;
    }
    
    /**
     * Get system status
     */
    private function getSystemStatus()
    {
        $status = 'healthy';
        $messages = [];
        
        // Check backup age
        $lastBackupAge = $this->getLastBackupAge();
        if ($lastBackupAge > 50) {
            $status = 'critical';
            $messages[] = "Last backup is {$lastBackupAge} hours old";
        } elseif ($lastBackupAge > 26) {
            $status = 'warning';
            $messages[] = "Last backup is {$lastBackupAge} hours old";
        }
        
        // Check disk usage
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage > 90) {
            $status = 'critical';
            $messages[] = "Disk usage critical: {$diskUsage}%";
        } elseif ($diskUsage > 80) {
            if ($status != 'critical') $status = 'warning';
            $messages[] = "Disk usage high: {$diskUsage}%";
        }
        
        // Check for recent incidents
        $incidentsFile = $this->backupDir . '/logs/incidents.log';
        if (file_exists($incidentsFile)) {
            $recentIncidents = $this->countRecentLines($incidentsFile, 3600); // Last hour
            if ($recentIncidents > 5) {
                if ($status != 'critical') $status = 'warning';
                $messages[] = "{$recentIncidents} incidents in last hour";
            }
        }
        
        return [
            'level' => $status,
            'messages' => $messages,
            'color' => $this->getStatusColor($status)
        ];
    }
    
    /**
     * Get recent issues from incident log
     */
    private function getRecentIssues()
    {
        $issues = [];
        $incidentsFile = $this->backupDir . '/logs/incidents.log';
        
        if (file_exists($incidentsFile)) {
            $lines = array_reverse(file($incidentsFile, FILE_IGNORE_NEW_LINES));
            $count = 0;
            
            foreach ($lines as $line) {
                if ($count >= 10) break;
                
                // Parse incident line
                if (preg_match('/\[(.*?)\] Issue: (.*?) \| Action: (.*?) \| Result: (.*)/', $line, $matches)) {
                    $issues[] = [
                        'time' => $matches[1],
                        'issue' => $matches[2],
                        'action' => $matches[3],
                        'result' => $matches[4],
                        'age' => $this->timeAgo($matches[1])
                    ];
                    $count++;
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Helper: Get last backup age in hours
     */
    private function getLastBackupAge()
    {
        $allBackups = array_merge(
            glob($this->backupDir . '/db/*.sql.gz'),
            glob($this->backupDir . '/files/*.tar.gz')
        );
        
        if (empty($allBackups)) {
            return 999; // No backups
        }
        
        $times = array_map('filemtime', $allBackups);
        $newest = max($times);
        
        return round((time() - $newest) / 3600, 1);
    }
    
    /**
     * Helper: Get last backup time
     */
    private function getLastBackupTime()
    {
        $age = $this->getLastBackupAge();
        
        if ($age < 1) {
            return 'Less than 1 hour ago';
        } elseif ($age < 24) {
            return round($age) . ' hours ago';
        } else {
            return round($age / 24, 1) . ' days ago';
        }
    }
    
    /**
     * Helper: Get disk usage percentage
     */
    private function getDiskUsage()
    {
        $output = shell_exec("df " . escapeshellarg($this->backupDir) . " | awk 'NR==2 {print int($5)}'");
        return intval(trim($output));
    }
    
    /**
     * Helper: Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Helper: Get file age string
     */
    private function getFileAge($file)
    {
        $age = time() - filemtime($file);
        
        if ($age < 3600) {
            return round($age / 60) . ' min';
        } elseif ($age < 86400) {
            return round($age / 3600) . ' hours';
        } else {
            return round($age / 86400) . ' days';
        }
    }
    
    /**
     * Helper: Get status color
     */
    private function getStatusColor($status)
    {
        switch ($status) {
            case 'healthy':
                return 'green';
            case 'warning':
                return 'yellow';
            case 'critical':
                return 'red';
            default:
                return 'gray';
        }
    }
    
    /**
     * Helper: Count recent lines in log file
     */
    private function countRecentLines($file, $seconds)
    {
        $count = 0;
        $cutoff = time() - $seconds;
        
        $lines = array_reverse(file($file, FILE_IGNORE_NEW_LINES));
        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\]/', $line, $matches)) {
                $timestamp = strtotime($matches[1]);
                if ($timestamp && $timestamp > $cutoff) {
                    $count++;
                } else {
                    break;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Helper: Time ago string
     */
    private function timeAgo($timestamp)
    {
        $time = strtotime($timestamp);
        $diff = time() - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' min ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } else {
            return floor($diff / 86400) . ' days ago';
        }
    }
}
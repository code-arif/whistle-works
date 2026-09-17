<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogViewerController extends Controller
{
    /**
     * Display the Log Viewer dashboard.
     */
    public function index(Request $request): Response
    {
        $files = $this->getAvailableLogFiles();
        $selectedFile = $this->sanitizeFileName($request->query('file', 'laravel.log'), $files);

        $logData = $this->parseLogFile($selectedFile);

        return Inertia::render('Logs/Index', [
            'files'         => $files,
            'selectedFile'  => $selectedFile,
            'entries'       => $logData['entries'],
            'stats'         => $logData['stats'],
            'fileInfo'      => $logData['file_info'],
        ]);
    }

    /**
     * Fetch log data asynchronously for live polling or switching tabs.
     */
    public function data(Request $request): JsonResponse
    {
        $files = $this->getAvailableLogFiles();
        $selectedFile = $this->sanitizeFileName($request->query('file', 'laravel.log'), $files);

        $logData = $this->parseLogFile($selectedFile);

        return response()->json([
            'success'      => true,
            'files'        => $files,
            'selectedFile' => $selectedFile,
            'entries'      => $logData['entries'],
            'stats'        => $logData['stats'],
            'fileInfo'     => $logData['file_info'],
        ]);
    }

    /**
     * Download the raw log file.
     */
    public function download(Request $request): BinaryFileResponse|RedirectResponse
    {
        $files = $this->getAvailableLogFiles();
        $filename = $this->sanitizeFileName($request->query('file', 'laravel.log'), $files);
        $filePath = storage_path('logs/' . $filename);

        if (!File::exists($filePath)) {
            return redirect()->back()->with('t-error', "Log file [{$filename}] does not exist.");
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Clear / truncate the specified log file.
     */
    public function clear(Request $request): RedirectResponse
    {
        $files = $this->getAvailableLogFiles();
        $filename = $this->sanitizeFileName($request->input('file', 'laravel.log'), $files);
        $filePath = storage_path('logs/' . $filename);

        try {
            if (File::exists($filePath)) {
                File::put($filePath, '');
            } else {
                File::put($filePath, '');
            }

            return redirect()->back()->with('t-success', "Log file [{$filename}] was cleared successfully.");
        } catch (\Throwable $th) {
            return redirect()->back()->with('t-error', "Failed to clear log file: " . $th->getMessage());
        }
    }

    /**
     * Get list of all .log files in storage/logs/ directory.
     */
    protected function getAvailableLogFiles(): array
    {
        $logPath = storage_path('logs');
        $result = [];

        if (File::isDirectory($logPath)) {
            $rawFiles = File::files($logPath);
            foreach ($rawFiles as $file) {
                if ($file->getExtension() === 'log') {
                    $result[] = [
                        'name'                 => $file->getFilename(),
                        'size'                 => $file->getSize(),
                        'size_formatted'       => $this->formatBytes($file->getSize()),
                        'updated_at'           => $file->getMTime(),
                        'updated_at_formatted' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
        }

        // Sort so that laravel.log is pinned first
        usort($result, function ($a, $b) {
            if ($a['name'] === 'laravel.log') return -1;
            if ($b['name'] === 'laravel.log') return 1;
            return strcmp($a['name'], $b['name']);
        });

        return $result;
    }

    /**
     * Sanitize filename to prevent directory traversal.
     */
    protected function sanitizeFileName(string $filename, array $availableFiles): string
    {
        $base = basename($filename);
        if (!Str::endsWith($base, '.log')) {
            $base = 'laravel.log';
        }

        return $base;
    }

    /**
     * Parse log file content safely into structured items.
     */
    protected function parseLogFile(string $filename): array
    {
        $filePath = storage_path('logs/' . $filename);

        if (!File::exists($filePath)) {
            return [
                'entries'   => [],
                'stats'     => [
                    'total'    => 0,
                    'errors'   => 0,
                    'warnings' => 0,
                    'infos'    => 0,
                    'debugs'   => 0,
                ],
                'file_info' => [
                    'name'           => $filename,
                    'size'           => 0,
                    'size_formatted' => '0 B',
                    'updated_at'     => 'Never',
                    'is_empty'       => true,
                ],
            ];
        }

        $fileSize = File::size($filePath);
        $lastModified = File::lastModified($filePath);

        // Read up to 2MB from the tail to avoid memory exhaustion
        $rawContent = $this->readTail($filePath, 2 * 1024 * 1024);

        if (empty(trim($rawContent))) {
            return [
                'entries'   => [],
                'stats'     => [
                    'total'    => 0,
                    'errors'   => 0,
                    'warnings' => 0,
                    'infos'    => 0,
                    'debugs'   => 0,
                ],
                'file_info' => [
                    'name'           => $filename,
                    'size'           => $fileSize,
                    'size_formatted' => $this->formatBytes($fileSize),
                    'updated_at'     => date('Y-m-d H:i:s', $lastModified),
                    'is_empty'       => true,
                ],
            ];
        }

        $lines = preg_split("/\r\n|\n|\r/", $rawContent);
        $entries = [];
        $currentEntry = null;

        // Monolog standard pattern: [YYYY-MM-DD HH:MM:SS] env.LEVEL: Message
        $monologRegex = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[\+-]\d{2}:\d{2})?)\]\s*(?:([a-zA-Z0-9_\-\.]+)\.)?([a-zA-Z0-9_\-]+):\s*(.*)$/';

        // CLI output pattern (e.g. Reverb: "   INFO  Starting server...", or "[2026-09-17] INFO ...")
        $cliRegex = '/^(?:\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})\]\s*)?(?:[ ]{2,})?(INFO|ERROR|WARN|WARNING|DEBUG|NOTICE|CRITICAL|ALERT|EMERGENCY)\s*[:\-]?\s*(.*)$/i';

        $idCounter = 1;
        $stats = [
            'total'    => 0,
            'errors'   => 0,
            'warnings' => 0,
            'infos'    => 0,
            'debugs'   => 0,
        ];

        foreach ($lines as $line) {
            // Check Monolog match
            if (preg_match($monologRegex, $line, $matches)) {
                if ($currentEntry) {
                    $entries[] = $currentEntry;
                }

                $level = strtoupper($matches[3] ?? 'INFO');
                $this->incrementStats($stats, $level);

                $currentEntry = [
                    'id'          => $idCounter++,
                    'timestamp'   => $matches[1],
                    'environment' => $matches[2] ?: 'local',
                    'level'       => $level,
                    'message'     => $matches[4] ?? '',
                    'stack_trace' => '',
                ];
            } 
            // Check CLI / Reverb line match
            elseif (preg_match($cliRegex, $line, $matches)) {
                if ($currentEntry) {
                    $entries[] = $currentEntry;
                }

                $level = strtoupper($matches[2] ?? 'INFO');
                if ($level === 'WARN') $level = 'WARNING';
                $this->incrementStats($stats, $level);

                $currentEntry = [
                    'id'          => $idCounter++,
                    'timestamp'   => $matches[1] ?: date('Y-m-d H:i:s', $lastModified),
                    'environment' => 'reverb/cli',
                    'level'       => $level,
                    'message'     => $matches[3] ?? '',
                    'stack_trace' => '',
                ];
            } else {
                // Continuation line (Stacktrace or context)
                if ($currentEntry) {
                    if (empty($currentEntry['stack_trace'])) {
                        $currentEntry['stack_trace'] = $line;
                    } else {
                        $currentEntry['stack_trace'] .= "\n" . $line;
                    }
                } elseif (!empty(trim($line))) {
                    // Stray initial line
                    $level = 'INFO';
                    $this->incrementStats($stats, $level);
                    $currentEntry = [
                        'id'          => $idCounter++,
                        'timestamp'   => date('Y-m-d H:i:s', $lastModified),
                        'environment' => 'system',
                        'level'       => $level,
                        'message'     => $line,
                        'stack_trace' => '',
                    ];
                }
            }
        }

        if ($currentEntry) {
            $entries[] = $currentEntry;
        }

        // Limit to latest 600 entries to maintain rapid frontend reactivity, reversed so newest first
        $entries = array_reverse($entries);
        $entries = array_slice($entries, 0, 600);

        return [
            'entries'   => $entries,
            'stats'     => $stats,
            'file_info' => [
                'name'           => $filename,
                'size'           => $fileSize,
                'size_formatted' => $this->formatBytes($fileSize),
                'updated_at'     => date('Y-m-d H:i:s', $lastModified),
                'is_empty'       => empty($entries),
            ],
        ];
    }

    /**
     * Read the last N bytes of a file safely.
     */
    protected function readTail(string $filePath, int $maxBytes): string
    {
        $size = filesize($filePath);
        if ($size <= $maxBytes) {
            return (string) file_get_contents($filePath);
        }

        $fp = fopen($filePath, 'r');
        if (!$fp) {
            return '';
        }

        fseek($fp, -$maxBytes, SEEK_END);
        // Discard first partial line
        fgets($fp);
        $content = (string) stream_get_contents($fp);
        fclose($fp);

        return $content;
    }

    /**
     * Increment level counters.
     */
    protected function incrementStats(array &$stats, string $level): void
    {
        $stats['total']++;
        if (in_array($level, ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT'])) {
            $stats['errors']++;
        } elseif ($level === 'WARNING') {
            $stats['warnings']++;
        } elseif (in_array($level, ['INFO', 'NOTICE'])) {
            $stats['infos']++;
        } elseif ($level === 'DEBUG') {
            $stats['debugs']++;
        }
    }

    /**
     * Format raw byte count into human-readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

<?php
// Secure audio streamer - requires session
$audioId = $_GET['id'] ?? '';
if (empty($audioId) || !preg_match('/^[0-9]+$/', $audioId)) {
    http_response_code(400);
    exit;
}

// Load audio from DB
$env = parse_ini_file(__DIR__ . '/.env');
$db = new mysqli(
    $env['database.default.hostname'] ?? 'localhost',
    $env['database.default.username'] ?? '',
    $env['database.default.password'] ?? '',
    $env['database.default.database'] ?? ''
);

$res = $db->query("SELECT file_path, format, name FROM sp_call_audios WHERE id = " . intval($audioId));
$row = $res->fetch_assoc();
if (!$row || empty($row['file_path']) || !file_exists($row['file_path'])) {
    http_response_code(404);
    echo 'Audio not found';
    exit;
}

$filePath = $row['file_path'];
$mimeType = match($row['format'] ?? 'mp3') {
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'opus' => 'audio/opus',
    'ogg', 'oga' => 'audio/ogg',
    'm4a', 'aac' => 'audio/mp4',
    default => 'audio/mpeg',
};

$fileSize = filesize($filePath);
$fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', ($row['name'] ?? 'audio') . '.' . ($row['format'] ?? 'mp3'));

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

// Handle range requests for seeking
if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
    $start = intval($matches[1]);
    $end = $matches[2] !== '' ? intval($matches[2]) : $fileSize - 1;
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
    header('Content-Length: ' . ($end - $start + 1));
    $fp = fopen($filePath, 'rb');
    fseek($fp, $start);
    $remaining = $end - $start + 1;
    while ($remaining > 0 && !feof($fp)) {
        $chunk = min(8192, $remaining);
        echo fread($fp, $chunk);
        $remaining -= $chunk;
    }
    fclose($fp);
} else {
    readfile($filePath);
}

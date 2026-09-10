<?php
/** Export the configured WordPress database to db/wordpress_hoten.sql. */

declare(strict_types=1);

require dirname(__DIR__) . '/wp-load.php';

$candidates = glob('C:/wamp64/bin/mysql/mysql*/bin/mysqldump.exe');
if (!$candidates) {
    fwrite(STDERR, "Cannot find mysqldump.exe in Wampserver.\n");
    exit(1);
}
rsort($candidates, SORT_NATURAL);
$mysqldump = $candidates[0];

$outputDirectory = dirname(__DIR__) . '/db';
if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
    fwrite(STDERR, "Cannot create the db directory.\n");
    exit(1);
}
$outputFile = $outputDirectory . '/wordpress_hoten.sql';

$host = DB_HOST;
$port = null;
if (preg_match('/^(.+):(\d+)$/', DB_HOST, $matches)) {
    $host = $matches[1];
    $port = $matches[2];
}

$command = [
    $mysqldump,
    '--host=' . $host,
    '--user=' . DB_USER,
    '--password=' . DB_PASSWORD,
    '--default-character-set=utf8mb4',
    '--single-transaction',
    '--routines',
    '--triggers',
    '--events',
    '--no-tablespaces',
    '--result-file=' . $outputFile,
];
if ($port !== null) {
    $command[] = '--port=' . $port;
}
$command[] = DB_NAME;

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$process = proc_open($command, $descriptors, $pipes, dirname(__DIR__), null, ['bypass_shell' => true]);
if (!is_resource($process)) {
    fwrite(STDERR, "Cannot start mysqldump.\n");
    exit(1);
}

fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0) {
    fwrite(STDERR, trim($stderr ?: $stdout) . PHP_EOL);
    exit($exitCode);
}

clearstatcache(true, $outputFile);
echo json_encode([
    'status' => 'EXPORTED',
    'database' => DB_NAME,
    'file' => str_replace('\\', '/', substr($outputFile, strlen(dirname(__DIR__)) + 1)),
    'bytes' => filesize($outputFile),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;


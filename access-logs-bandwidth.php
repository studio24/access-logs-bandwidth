<?php
/**
 * Test script to parse access logs in data/ folder and output bandwidth used in past month
 *
 * Works with Apache common and combined log formats
 *
 * @todo refactor into a CLI command (Symfony CLI)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$timeStart = microtime(true);

require_once 'vendor/autoload.php';

use \wapmorgan\UnifiedArchive\UnifiedArchive;

$end = new DateTime();
$start = clone $end;
$start->modify('-1 month');

$bandwidth = 0;
$accessCount = 0;
$ignoredLines = 0;

$title = "Access Logs Bandwidth";
echo PHP_EOL . $title . PHP_EOL;
echo str_pad('', strlen($title), '-') . PHP_EOL . PHP_EOL;

$dir = new DirectoryIterator(__DIR__ . '/data');
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {

        echo "Parsing file: " . $fileinfo->getPathname() . PHP_EOL;

        if ($fileinfo->getExtension() === 'gz') {
            $archive = UnifiedArchive::open($fileinfo->getPathname());
            if ($archive === null) {
                throw new Exception(sprintf('Cannot open archive file %s', $fileinfo->getPathname()));
            }
            parseFile($archive->getFileContent($fileinfo->getPathname()));
        } else {
            parseFile(file_get_contents($fileinfo->getPathname()));
        }
    }
}


$timeEnd = microtime(true);
$time = $timeEnd - $timeStart;

echo PHP_EOL;
echo sprintf("From %s to %s", $start->format('r'), $end->format('r')) . PHP_EOL;
echo 'Access count: ' . number_format($accessCount) . PHP_EOL;
echo 'Bandwidth (Gb): ' . number_format(round($bandwidth/1073741824, 2), 2) . PHP_EOL;
echo $ignoredLines . ' lines were ignored (not in date range or did not match log format pattern)' . PHP_EOL;
echo PHP_EOL;
echo 'Script took ' . number_format($time, 2) . ' seconds to run. Thanks for all the fish!' . PHP_EOL;
echo PHP_EOL;

function parseFile(string $fileBody, $format = 'combined')
{
    $separator = "\n";
    $line = strtok($fileBody, $separator);

    while ($line !== false) {
        parseLine($line, $format);
        $line = strtok($separator);
    }
}

/**
 * @see https://httpd.apache.org/docs/2.4/logs.html
 * @param string $line
 */
function parseLine(string $line)
{
    global $bandwidth, $accessCount, $ignoredLines, $start, $end;

    // Build regex
    $timeRegex = "\[([\d]{2}/[a-z]{3}/[\d]{4}:[\d]{2}:[\d]{2}:[\d]{2} [+-]{1}[\d]{4})\]";
    $requestRegex = '"[^"]+"';
    $statusRegex = '[\d]{3}';
    $bytesRegex = '([\d\-]+)';
    $regex = "!$timeRegex $requestRegex $statusRegex $bytesRegex!i";

    if (preg_match($regex, $line, $m)) {
        $time = $m[1];
        $bytes = (int) $m[2];

        $time = DateTime::createFromFormat('d/M/Y:H:i:s O', $time);
        if ($time >= $start && $time <= $end) {
            $bandwidth += $bytes;
            $accessCount++;
        } else {
            $ignoredLines++;
        }
    }
}
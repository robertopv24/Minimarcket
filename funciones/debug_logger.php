<?php
/**
 * Simple logging utility for debugging
 * Writes to /tmp/minimarcket_debug.log
 */

function debugLog($message, $context = [])
{
    // Intentar escribir en admin/log.txt
    $logFile = __DIR__ . '/../admin/log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$message}{$contextStr}\n";

    // Usar @ para suprimir errores y verificar si se puede escribir
    if (@file_put_contents($logFile, $logMessage, FILE_APPEND) === false) {
        // Fallback a /tmp si falla
        $logFile = '/tmp/minimarcket_debug.log';
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

function clearDebugLog()
{
    $logFile = __DIR__ . '/../admin/log.txt';
    if (file_exists($logFile)) {
        @unlink($logFile);
    }

    // También limpiar el de tmp
    $tmpLog = '/tmp/minimarcket_debug.log';
    if (file_exists($tmpLog)) {
        @unlink($tmpLog);
    }
}

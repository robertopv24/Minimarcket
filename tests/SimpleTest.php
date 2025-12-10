<?php

class SimpleTest
{
    public static function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            echo "❌ FAILED: $message\n";
            echo "   Expected: " . print_r($expected, true) . "\n";
            echo "   Actual:   " . print_r($actual, true) . "\n";
            exit(1);
        } else {
            echo "✅ PASSED: $message\n";
        }
    }

    public static function assertTrue($condition, $message = '')
    {
        if ($condition !== true) {
            echo "❌ FAILED: $message (Condition is false)\n";
            exit(1);
        } else {
            echo "✅ PASSED: $message\n";
        }
    }

    public static function assertNotNull($actual, $message = '')
    {
        if ($actual === null) {
            echo "❌ FAILED: $message (Expected not null, got null)\n";
            exit(1);
        } else {
            echo "✅ PASSED: $message\n";
        }
    }
}
?>
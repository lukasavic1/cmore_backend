<?php
/**
 * date: 9.4.2026.
 * owner: lukasavic18@gmail.com
 *
 * Configures shared Pest testing behavior and global helpers for the
 * test suite.
 */

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific
| PHPUnit test case class. By default,
| that class is "PHPUnit\Framework\TestCase".
| You may extend this to use a different test case class.
|
*/

// #region agent log
file_put_contents(
    '/Users/lukasavic/Documents/cmore-test/.cursor/debug-545ce9.log',
    json_encode([
        'sessionId' => '545ce9',
        'runId' => 'pre-fix',
        'hypothesisId' => 'H1',
        'location' => 'tests/Pest.php',
        'message' => 'Pest bootstrap environment check',
        'data' => [
            'mockeryClassExists' => class_exists('Mockery'),
            'mockeryFileExists' => file_exists(
                __DIR__.'/../vendor/mockery/mockery/library/Mockery.php'
            ),
            'testCaseClassExists' => class_exists(Tests\TestCase::class),
        ],
        'timestamp' => (int) (microtime(true) * 1000),
    ], JSON_UNESCAPED_SLASHES).PHP_EOL,
    FILE_APPEND
);
// #endregion

uses(Tests\TestCase::class)->in('Feature');

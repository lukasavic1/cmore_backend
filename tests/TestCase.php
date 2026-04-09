<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // #region agent log
        file_put_contents(
            '/Users/lukasavic/Documents/cmore-test/.cursor/debug-545ce9.log',
            json_encode([
                'sessionId' => '545ce9',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H2',
                'location' => 'tests/TestCase.php:setUp',
                'message' => 'TestCase setup reached',
                'data' => [
                    'baseClass' => BaseTestCase::class,
                    'hasCreateApplicationMethod' => method_exists(
                        $this,
                        'createApplication'
                    ),
                    'mockeryClassExists' => class_exists('Mockery'),
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
        // #endregion
    }
}

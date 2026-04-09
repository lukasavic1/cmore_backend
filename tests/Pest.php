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

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

uses(Tests\TestCase::class)->in('Feature');

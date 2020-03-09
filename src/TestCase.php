<?php

namespace Exolnet\Test;

use Exolnet\Test\DatabaseMigrators\DatabaseMigrator;
use Exolnet\Test\Traits\AssertionsTrait;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\App;
use Mockery;
use Throwable;

abstract class TestCase extends LaravelTestCase
{
    use AssertionsTrait;

    /**
     * @var \Exolnet\Test\DatabaseMigratorFactory
     */
    protected static $databaseMigrator;

    /**
     * @var bool
     */
    protected static $migrationFailed = false;

    /**
     * @return void
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (self::$migrationFailed) {
            $this->markTestSkipped('Previous migration failed.');
            return;
        }

        try {
            $this->getDatabaseMigrator()->run();
        } catch (Throwable $e) {
            self::$migrationFailed = true;
            throw $e;
        }
    }

    /**
     * @return \Exolnet\Test\DatabaseMigrators\DatabaseMigrator
     */
    protected function getDatabaseMigrator(): DatabaseMigrator
    {
        if (! self::$databaseMigrator) {
            self::$databaseMigrator = (new DatabaseMigratorFactory)->create();
        }

        return self::$databaseMigrator;
    }

    /**
     * @param mixed|      $abstract
     * @param object|null $mockInstance
     * @return \Mockery\MockInterface
     */
    protected function mockAppInstance($abstract, $mockInstance = null)
    {
        if (! $mockInstance) {
            $mockInstance = Mockery::mock($abstract);
        }

        App::instance($abstract, $mockInstance);

        return $mockInstance;
    }
}

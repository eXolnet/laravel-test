<?php

namespace Exolnet\Test;

use Closure;
use Exception;
use Exolnet\Test\Traits\AssertionsTrait;
use Faker\Factory as FakerFactory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
use Mockery;
use RuntimeException;

abstract class TestCase extends BaseTestCase
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
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * @var bool
     */
    protected static $forceBoot = false;

    /**
     * @var bool
     */
    protected static $environmentSetup = false;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->hotfixSqlite();
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $testedPaths = [
            __DIR__ . '/../../../../bootstrap/app.php',
            __DIR__ . '/../../../../../bootstrap/app.php',
        ];

        $app = null;
        foreach ($testedPaths as $testedPath) {
            if (file_exists($testedPath)) {
                $app = require $testedPath;
                break;
            }
        }

        if (! $app) {
            throw new RuntimeException('Could not find bootstrap/app.php');
        }

        $app->loadEnvironmentFrom('.env.testing');

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Setup the test environment.
     *
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->faker = FakerFactory::create();

        if (self::$migrationFailed) {
            $this->markTestSkipped('Previous migration failed.');
            return;
        }

        try {
            $this->setupDatabaseMigrator();
            self::$databaseMigrator->run();
        } catch (Exception $e) {
            self::$migrationFailed = true;
            throw $e;
        }

        self::bootModels();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     * @throws \Throwable
     */
    public function tearDown(): void
    {
        if (! self::$migrationFailed) {
            DB::disconnect();
            Mockery::close();

            $this->tearDownModels();

            $this->app->flush();
            $this->app = null;
        }

        parent::tearDown();
    }

    /**
     * @return void
     */
    protected function setupDatabaseMigrator()
    {
        if (self::$databaseMigrator) {
            return;
        }

        $databaseMigratorFactory = new DatabaseMigratorFactory();
        self::$databaseMigrator = $databaseMigratorFactory->create();
    }

    /**
     * @return void
     */
    protected function bootModels()
    {
        // TODO: Remove this when Laravel fixes the issue with model booting in tests
        if (self::$forceBoot) {
            $this->setUpModels();
        } else {
            self::$forceBoot = true;
        }
    }

    /**
     * @return void
     */
    protected function setUpModels()
    {
    }

    /**
     * @return void
     */
    protected function tearDownModels()
    {
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

    private function hotfixSqlite()
    {
        Connection::resolverFor('sqlite', function ($connection, $database, $prefix, $config) {
            return new class($connection, $database, $prefix, $config) extends SQLiteConnection
            {
                public function getSchemaBuilder()
                {
                    if ($this->schemaGrammar === null) {
                        $this->useDefaultSchemaGrammar();
                    }
                    return new class($this) extends SQLiteBuilder
                    {
                        protected function createBlueprint($table, Closure $callback = null)
                        {
                            return new class($table, $callback) extends Blueprint
                            {
                                public function dropForeign($index)
                                {
                                    return new Fluent();
                                }
                            };
                        }
                    };
                }
            };
        });
    }
}

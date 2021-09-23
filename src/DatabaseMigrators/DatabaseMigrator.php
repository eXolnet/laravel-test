<?php

namespace Exolnet\Test\DatabaseMigrators;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class DatabaseMigrator
{
    /**
     * @return void
     */
    public function run(): void
    {
        if (Schema::hasTable('migrations')) {
            Artisan::call('migrate:reset');
        }

        $this->freshDatabase();
    }

    /**
     * @return void
     */
    protected function freshDatabase(): void
    {
        $this->migrateDatabase();
        $this->seedTestData();
    }

    /**
     * @return void
     */
    protected function migrateDatabase(): void
    {
        Artisan::call('migrate');
    }

    /**
     * @return void
     */
    public function seedTestData(): void
    {
        if (! file_exists(App::basePath('database/seeds/TestSeeder.php')) && // eslint-disable-line
            ! file_exists(App::basePath('database/seeders/TestSeeder.php')) // eslint-disable-line
        ) { // eslint-disable-line
            return;
        }

        Artisan::call('db:seed', ['--class' => 'TestSeeder']);
    }
}

<?php

namespace Exolnet\Test\DatabaseMigrators;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class DatabaseMigrator
{
    /**
     *
     */
    public function __construct()
    {
    }

    public function run()
    {
        if (Schema::hasTable('migrations')) {
            Artisan::call('migrate:reset');
        }
        Artisan::call('migrate');
        if (file_exists(App::basePath('database/seeds/TestSeeder.php'))) {
            Artisan::call('db:seed', ['--class' => 'TestSeeder']);
        }
    }
}

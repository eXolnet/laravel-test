<?php

namespace Exolnet\Test;

use Exolnet\Test\DatabaseMigrators\DatabaseMigrator;
use Exolnet\Test\DatabaseMigrators\SQLiteDatabaseMigrator;
use Illuminate\Support\Facades\Config;

class DatabaseMigratorFactory
{
    /**
     * @return DatabaseMigrator|SQLiteDatabaseMigrator
     */
    public function create()
    {
        if ($this->isSQLite() && $this->getSQLiteFile()) {
            return new SQLiteDatabaseMigrator($this->getSQLiteFile());
        } else {
            return new DatabaseMigrator();
        }
    }

    /**
     * @return bool
     */
    protected function isSQLite()
    {
        return strcasecmp(array_get($this->getDefaultConnectionConfiguration(), 'driver', ''), 'sqlite') === 0;
    }

    /**
     * @return mixed|null
     */
    protected function getSQLiteFile()
    {
        $file = array_get($this->getDefaultConnectionConfiguration(), 'database');
        if ($file === ':memory:') {
            return null;
        }
        return $file;
    }

    protected function getDefaultConnectionConfiguration()
    {
        $default = Config::get('database.default');
        return Config::get('database.connections.' . $default, []);
    }
}

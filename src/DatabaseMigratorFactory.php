<?php

namespace Exolnet\Test;

use Exolnet\Test\DatabaseMigrators\DatabaseMigrator;
use Exolnet\Test\DatabaseMigrators\SQLiteDatabaseMigrator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class DatabaseMigratorFactory
{
    /**
     * @return \Exolnet\Test\DatabaseMigrators\DatabaseMigrator
     */
    public function create(): DatabaseMigrator
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
    protected function isSQLite(): bool
    {
        return strcasecmp(Arr::get($this->getDefaultConnectionConfiguration(), 'driver', ''), 'sqlite') === 0;
    }

    /**
     * @return string|null
     */
    protected function getSQLiteFile(): ?string
    {
        $file = Arr::get($this->getDefaultConnectionConfiguration(), 'database');

        if ($file === ':memory:') {
            return null;
        }

        return $file;
    }

    /**
     * @return array
     */
    protected function getDefaultConnectionConfiguration(): array
    {
        $default = Config::get('database.default');

        return Config::get('database.connections.' . $default, []);
    }
}

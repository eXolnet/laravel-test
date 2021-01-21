<?php

namespace Exolnet\Test\DatabaseMigrators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class SQLiteDatabaseMigrator extends DatabaseMigrator
{
    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $cloneFile;

    /**
     * @var string
     */
    protected $bomFile;

    /**
     * @var \stdClass
     */
    protected $bomData;

    /**
     * @var string
     */
    protected $sqliteSignature;

    /**
     * @var string
     */
    protected $filesSignature;

    /**
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->filesystem = new Filesystem();
        $this->file = $file;
        $this->cloneFile = $this->getCloneFilename($this->file);
        $this->bomFile = $this->getBOMFilename($this->file);
        $this->bomData = null;
        $this->sqliteSignature = null;
        $this->filesSignature = null;
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function run(): void
    {
        if (! $this->booted) {
            $this->initialMigration();
            $this->booted = true;
        } else {
            $this->restore();
        }
    }

    /**
     * @return void
     */
    protected function configurePragma(): void
    {
        // Enable foreign keys for the current connection/file
        DB::statement('PRAGMA foreign_keys = ON;');
        // Create sqlite-journal in memory only (instead of creating disk files)
        DB::statement('PRAGMA journal_mode = MEMORY;');
        // Do not wait for OS after sending write commands
        DB::statement('PRAGMA synchronous = OFF;');
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function initialMigration()
    {
        if ($this->canReuseClone()) {
            $this->restore();
            return;
        }

        $this->empty($this->file);
        $this->empty($this->cloneFile);

        $this->configurePragma();

        $this->freshDatabase();

        $this->filesystem->copy($this->file, $this->cloneFile);

        $this->generateBOM();
    }

    /**
     * @return void
     */
    protected function restore(): void
    {
        $this->filesystem->copy($this->cloneFile, $this->file);

        $this->configurePragma();
    }

    /**
     * @param string $file
     */
    protected function empty(string $file): void
    {
        $this->filesystem->put($file, '');
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getCloneFilename(string $file): string
    {
        $dirname = pathinfo($file, PATHINFO_DIRNAME);
        $filename = pathinfo($file, PATHINFO_BASENAME);

        return $dirname . DIRECTORY_SEPARATOR . '_' . $filename;
    }

    /**
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function canReuseClone(): bool
    {
        return $this->bomFileExists() && $this->sqliteSignatureMatches() && $this->filesSignatureMatches();
    }

    /**
     * @return bool
     */
    protected function bomFileExists(): bool
    {
        return $this->filesystem->exists($this->bomFile);
    }

    /**
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function filesSignatureMatches(): bool
    {
        $signature = $this->getFilesSignature();

        $data = $this->getBOMData();

        return $signature === $data->files;
    }

    /**
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function sqliteSignatureMatches(): bool
    {
        if (! $this->filesystem->exists($this->cloneFile)) {
            return false;
        }

        $cloneFileHash = $this->getSqliteSignature();

        $data = $this->getBOMData();

        return $cloneFileHash === $data->sqlite;
    }

    /**
     * @return \stdClass
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getBOMData(): \stdClass
    {
        return $this->bomData ?? ($this->bomData = $this->readBOM());
    }

    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getSqliteSignature(): string
    {
        return $this->sqliteSignature ?? ($this->sqliteSignature = $this->calculateSqliteSignature());
    }

    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getFilesSignature(): string
    {
        return $this->filesSignature ?? ($this->filesSignature = $this->calculateFilesSignature());
    }

    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function calculateSqliteSignature(): string
    {
        return $this->hashFile($this->cloneFile);
    }

    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function calculateFilesSignature(): string
    {
        $files = glob(App::basePath('database/{migrations,seeds,seeders}/*.php'), GLOB_BRACE);

        $signature = '';
        foreach ($files as $file) {
            $signature .= $this->hashFile($file);
        }

        return sha1($signature);
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getBOMFilename(string $file): string
    {
        $dirname = pathinfo($file, PATHINFO_DIRNAME);
        $filename = pathinfo($file, PATHINFO_BASENAME);

        return $dirname . DIRECTORY_SEPARATOR . $filename . '.json';
    }

    /**
     * @return \stdClass
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function readBOM(): \stdClass
    {
        return json_decode($this->filesystem->get($this->bomFile));
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function generateBOM(): void
    {
        $data = [
            'files'  => $this->getFilesSignature(),
            'sqlite' => $this->getSqliteSignature(),
        ];

        $this->filesystem->put($this->bomFile, json_encode($data));

        $this->bomData = (object)$data;
    }

    /**
     * @param string $path
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function hashFile(string $path): string
    {
        if (! $this->filesystem->isFile($path)) {
            throw new FileNotFoundException("File does not exist at path {$path}");
        }

        return sha1_file($path);
    }
}

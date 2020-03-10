<?php

namespace Exolnet\Test\DatabaseMigrators;

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
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->filesystem = new Filesystem();
        $this->file = $file;
        $this->cloneFile = $this->getCloneFilename($this->file);
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
        $signature = $this->calculateFilesSignature();
        if ($this->canReuseClone($signature)) {
            $this->restore();
            return;
        }

        $this->emptyAndChmod($this->file);
        $this->emptyAndChmod($this->cloneFile);

        $this->configurePragma();

        $this->freshDatabase();

        $this->filesystem->copy($this->file, $this->cloneFile);

        $this->generateBOM($signature);
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
    protected function emptyAndChmod(string $file): void
    {
        if ($this->filesystem->put($file, '') !== false) {
            chmod($file, 0777);
        }
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getCloneFilename(string $file): string
    {
        $dirname = pathinfo($file, PATHINFO_DIRNAME);
        $filename = pathinfo($file, PATHINFO_BASENAME);

        return $dirname . '/_' . $filename;
    }

    /**
     * @param string $signature
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function canReuseClone(string $signature): bool
    {
        return $this->bomFileExists() && $this->sqliteSignatureMatches() && $this->signatureMatches($signature);
    }

    /**
     * @return bool
     */
    protected function bomFileExists(): bool
    {
        $bomFilename = $this->getBOMFilename($this->file);

        return $this->filesystem->exists($bomFilename);
    }

    /**
     * @param string $signature
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function signatureMatches(string $signature): bool
    {
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

        $cloneFileHash = sha1($this->filesystem->get($this->cloneFile));

        $data = $this->getBOMData();

        return $cloneFileHash === $data->sqlite;
    }

    /**
     * @return \stdClass
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getBOMData(): \stdClass
    {
        $bomFilename = $this->getBOMFilename($this->file);
        return json_decode($this->filesystem->get($bomFilename));
    }

    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function calculateFilesSignature(): string
    {
        $files = glob(App::basePath('database/{migrations,seeds}/*.php'), GLOB_BRACE);

        $signature = '';
        foreach ($files as $file) {
            $signature .= sha1($this->filesystem->get($file));
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

        return $dirname . '/' . $filename . '.json';
    }

    /**
     * @param string $signature
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function generateBOM(string $signature): void
    {
        $data = [
            'files'  => $signature,
            'sqlite' => sha1($this->filesystem->get($this->cloneFile)),
        ];
        $this->filesystem->put($this->getBOMFilename($this->file), json_encode($data));
    }
}

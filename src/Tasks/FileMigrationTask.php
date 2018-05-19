<?php

namespace Dynamic\FileMigration\Tasks;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;

/**
 * Class FileSyncTask
 * @package Dynamic\FileSync\Tasks
 */
class FileMigrationTask extends BuildTask
{
    /**
     * @var string $title Shown in the overview on the {@link TaskRunner}
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title = 'SilverStripe File Migration Task';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'A task for migration a local file set to the SilverStripe Assets Filesystem.';

    /**
     * @var string
     */
    private static $segment = 'FileMigrationTask';

    /**
     * @var
     */
    private static $existing_file_system_path;

    /**
     * @var
     */
    private static $base_upload_folder = null;

    /**
     * @var bool
     */
    private static $migrate_directory_structure = true;

    /**
     * @var bool
     */
    private static $publish_on_migration = true;

    /**
     * @var
     */
    private $directory_map = [];

    /**
     * @param \SilverStripe\Control\HTTPRequest $request
     */
    public function run($request)
    {
        sleep(1);
        $count = 0;
        $migrateDirectoryStructure = $this->config()->get('migrate_directory_structure');
        $publish = $this->config()->get('publish_on_migration');

        $existing = File::get()->filter('LegacyPath:not', null)->column('LegacyPath');
        $existing = array_combine($existing, $existing);

        foreach ($this->traverseDirectory() as $file) {
            if (!isset($existing[$file])) {
                $folder = null;
                if ($migrateDirectoryStructure) {
                    $folder = $this->processDirectory($file);
                }

                $destinationName = ($migrateDirectoryStructure) ? $this->getOriginalFilename($file) : null;
                if ($this->migrateFile($file, $publish, $folder, $destinationName)) {
                    $count++;
                }
            }
        }
        $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        static::write_it("{$count} files migration total time: {$time}");
    }

    /**
     * @param null $directory
     * @return \Generator
     */
    protected function traverseDirectory($directory = null)
    {
        $directory = ($directory !== null) ?: $this->config()->get('existing_file_system_path');

        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::SELF_FIRST);
        $allowedExtensions = File::singleton()->config()->get('allowed_extensions');
        unset($allowedExtensions[0]);

        foreach ($objects as $name => $object) {
            if (is_file($name)) {
                if ($this->isValidFile($name, $allowedExtensions)) {
                    yield $name;
                }
            }
        }
    }

    /**
     * @param $fileName
     * @param $allowedExtensions
     */
    protected function isValidFile($fileName, $allowedExtensions)
    {
        foreach ($this->yieldExtensions($allowedExtensions) as $extension) {
            if (preg_match("/\.{$extension}/", $fileName) == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $allowedExtensions
     * @return \Generator
     */
    protected function yieldExtensions($allowedExtensions)
    {
        foreach ($allowedExtensions as $extension) {
            yield $extension;
        }
    }

    /**
     * @param $path
     * @return bool|null|Folder
     */
    protected function processDirectory($path)
    {
        $parts = explode('/', $path);
        $start = count($parts) - 4;

        while ($start < count($parts) - 1) {
            $reImplode[] = $parts[$start];
            $start++;
        }

        $checkDirectory = $this->config()->get('base_upload_folder') . '/' . implode('/', $reImplode);

        if (!isset($this->directory_map[$checkDirectory])) {
            if ($folder = Folder::find_or_make($this->config()->get('base_upload_folder') . '/' . implode('/', $reImplode))) {
                static::write_it("New folder created and cached: {$folder->Filename}");
                $this->directory_map[$checkDirectory] = $folder;
                return $folder;
            }
        } else {
            static::write_it("Using cached folder: {$this->directory_map[$checkDirectory]->Filename}");
            return $this->directory_map[$checkDirectory];
        }

        static::write_it("No folder could be found or created. File will be placed at the top level.");

        return null;
    }

    /**
     * @param $file
     * @return mixed
     */
    protected function getOriginalFilename($file)
    {
        $parts = explode('/', $file);
        return $parts[count($parts) - 1];
    }

    /**
     * @param $localPath
     * @param null $destination
     * @return bool
     * @throws \SilverStripe\ORM\ValidationException
     */
    protected function migrateFile($localPath, $publish, $destination = null, $originalFilename = null)
    {
        if (!$existing = File::get()->filter('LegacyPath', $localPath)->first()) {
            $newFile = File::create();
            $destinationName = ($destination !== null && $originalFilename !== null) ? $destination->Filename . $originalFilename : null;
            $newFile->setFromLocalFile($localPath, $destinationName);
            $newFile->LegacyPath = $localPath;
            $newFile->write();

            if ($publish) {
                $newFile->publishFile();
            }

            static::write_it("{$newFile->ID} - File {$newFile->Name} created.", false);
            unset($newFile);
            return true;
        }

        static::write_it("File {$existing->Name} already exists. Skipping");

        return false;
    }

    /**
     * @param string $message
     * @param bool $indent
     */
    protected static function write_it($message = '', $indent = true)
    {
        if (Director::is_cli()) {
            if ($indent) {
                echo "\t";
            }
            echo "{$message}\n";
        } else {
            if ($indent) {
                echo "<p style='margin-left: 25px;'>{$message}</p>";
            } else {
                echo "<p>{$message}</p>";
            }
        }
    }
}

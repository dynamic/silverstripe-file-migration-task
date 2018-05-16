<?php

namespace Dynamic\FileMigration\Extensions;

use SilverStripe\ORM\DataExtension;

/**
 * Class FileDataExtension
 * @package Dynamic\FileSync\Extensions
 */
class FileDataExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'LegacyPath' => 'Text',
    ];
}

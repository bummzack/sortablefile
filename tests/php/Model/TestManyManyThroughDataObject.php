<?php

namespace Bummzack\SortableFile\Tests\Model;

use Bummzack\SortableFile\Forms\SortableUploadField;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class TestManyManyThroughDataObject extends DataObject implements TestOnly
{
    private static $many_many = [
        'Files' => [
            'through' => TestFileLinkDataObject::class,
            'from' => 'Owner',
            'to' => 'File',
        ]
    ];

    private static $owns = [
        'Files'
    ];

    private static $cascade_deletes = [
        'Files'
    ];

    private static $extensions = [
        Versioned::class
    ];

    public function getCMSFields()
    {
        return FieldList::create(
            SortableUploadField::create('Files')
        );
    }

    public function getLinkedFiles()
    {
        return $this->Files()->sort('SortOrder');
    }
}

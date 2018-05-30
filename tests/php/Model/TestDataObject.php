<?php

namespace Bummzack\SortableFile\Tests\Model;

use Bummzack\SortableFile\Forms\SortableUploadField;
use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

class TestDataObject extends DataObject implements TestOnly
{

    private static $has_many = [
        'LinkedFilesFile' => FileLinkDataObject::class
    ];

    private static $many_many = [
        'Files' => File::class,
        'OtherFiles' => File::class,
        'LinkedFiles' => [
            'through' 			=> FileLinkDataObject::class,
            'from' 				=> 'Owner',
            'to' 				=> 'File',
        ]
    ];

    private static $many_many_extraFields = [
        'Files' => [ 'SortOrder' => 'Int' ],
        'OtherFiles' => [ 'Sort' => 'Int' ]
    ];

    private static $owns = [
        'FileLinks'
    ];

    public function getCMSFields()
    {
        return FieldList::create(
            SortableUploadField::create('Files'),
            SortableUploadField::create('OtherFiles')->setSortColumn('Sort')
        );
    }

    public function getLinkedFiles()
    {
        return $this->LinkedFiles()->sort('Sort');
    }
}

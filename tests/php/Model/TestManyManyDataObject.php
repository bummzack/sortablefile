<?php

namespace Bummzack\SortableFile\Tests\Model;

use Bummzack\SortableFile\Forms\SortableUploadField;
use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

class TestManyManyDataObject extends DataObject implements TestOnly
{
    private static $many_many = [
        'Files' => File::class,
        'OtherFiles' => File::class
    ];

    private static $many_many_extraFields = [
        'Files' => [ 'SortOrder' => 'Int' ],
        'OtherFiles' => [ 'Sort' => 'Int' ]
    ];

    public function getCMSFields()
    {
        return FieldList::create(
            SortableUploadField::create('Files'),
            SortableUploadField::create('OtherFiles')->setSortColumn('Sort')
        );
    }
}

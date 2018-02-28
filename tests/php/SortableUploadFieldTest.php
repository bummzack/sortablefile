<?php

namespace Bummzack\SortableFile\Tests;

use Bummzack\SortableFile\Forms\SortableUploadField;
use Bummzack\SortableFile\Tests\Model\TestDataObject;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

class SortableUploadFieldTest extends SapphireTest
{
    protected static $fixture_file = 'SortableUploadFieldTest.yml';

    protected static $extra_dataobjects = [
        TestDataObject::class
    ];

    public function setUp()
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);
        $this->logInWithPermission('ADMIN');
        TestAssetStore::activate(__DIR__ . '/assets');

        // Copy test images for each of the fixture references
        /** @var File $file */
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $file) {
            $sourcePath = __DIR__ . '/assets/' . $file->Name;
            $file->setFromLocalFile($sourcePath, $file->Filename);
        }
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testSortColumn()
    {
        $field = SortableUploadField::create('Files');

        $this->assertEquals('SortOrder', $field->getSortColumn(), 'Default value should be "SortOrder"');

        $field->setSortColumn('Sort');

        $this->assertEquals('Sort', $field->getSortColumn(), 'Changed value should be "Sort"');
    }

    public function testExistingSortOrder()
    {
        $obj = $this->objFromFixture(TestDataObject::class, 'obj1');

        $field = SortableUploadField::create('Files', 'Files', $obj->Files())->setRecord($obj);
        $this->assertEquals(['FileA', 'FileB', 'FileC', 'FileD'], $field->getItems()->column('Title'));

    }
}

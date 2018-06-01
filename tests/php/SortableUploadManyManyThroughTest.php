<?php

namespace Bummzack\SortableFile\Tests;

use Bummzack\SortableFile\Forms\SortableUploadField;
use Bummzack\SortableFile\Tests\Model\TestManyManyThroughDataObject;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Versioned\Versioned;

/**
 * Test Many Many Through relation functionality
 * @package Bummzack\SortableFile\Tests
 */
class SortableUploadManyManyThroughTest extends SapphireTest
{
    protected static $fixture_file = 'SortableUploadManyManyThroughTest.yml';

    protected static $extra_dataobjects = [
        TestManyManyThroughDataObject::class
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

    public function testExistingSortOrder()
    {
        $obj = $this->objFromFixture(TestManyManyThroughDataObject::class, 'obj1');

        $field = SortableUploadField::create('Files', 'Files', $obj->Files())->setRecord($obj);
        $this->assertEquals(['FileA', 'FileB', 'FileC', 'FileD'], $field->getItems()->column('Title'));

        // set field items in other order
        $field->setValue($obj->Files()->sort('FileHash'));
        // Items should be returned in the correct order though.
        $this->assertEquals(['FileA', 'FileB', 'FileC', 'FileD'], $field->getItems()->column('Title'));
    }

    public function testAddingFilesToNewRecord()
    {
        // Create a new DataObject which will have an unsaved relation
        $obj = TestManyManyThroughDataObject::create();

        // The file IDs to add, Should result in D,B,A,C
        $data = ['Files' => ['4','2','1','3']];

        $field = SortableUploadField::create('Files', 'Files', $obj->Files())->setRecord($obj);
        $field->setSubmittedValue($data);
        $field->saveInto($obj);
        $obj->write();

        $this->assertEquals(['FileD', 'FileB', 'FileA', 'FileC'], $obj->Files()->sort('SortOrder')->column('Title'));
        $this->assertEquals(['FileD', 'FileB', 'FileA', 'FileC'], $field->getItems()->column('Title'));

        // change the sort Order and remove an Item
        $field->setSubmittedValue(['Files' => ['1','2','3']]);
        $field->saveInto($obj);
        $obj->write();
        $this->assertEquals(['FileA', 'FileB', 'FileC'], $obj->Files()->sort('SortOrder')->column('Title'));
        $this->assertEquals(['FileA', 'FileB', 'FileC'], $field->getItems()->column('Title'));

        // Test persistance of sort-order if the incoming array-list doesn't contain the sort column
        $field->setSubmittedValue(['Files' => ['3','1','2']]);
        // Set the value from an arraylist without any sort-column
        $field->setValue(ArrayList::create(
            File::get()->byIDs([1,2,3])->toArray()
        ));
        $field->saveInto($obj);
        $obj->write();
        $this->assertEquals(['FileC', 'FileA', 'FileB'], $obj->Files()->sort('SortOrder')->column('Title'));
        $this->assertEquals(['FileC', 'FileA', 'FileB'], $field->getItems()->column('Title'));

        // Test with a newly added file
        $field->setSubmittedValue(['Files' => ['3','4','2','1']]);
        $field->saveInto($obj);
        $obj->write();

        $this->assertEquals(['FileC', 'FileD', 'FileB', 'FileA'], $obj->Files()->sort('SortOrder')->column('Title'));
        $this->assertEquals(['FileC', 'FileD', 'FileB', 'FileA'], $field->getItems()->column('Title'));
    }

    public function testVersionedSortOrder()
    {
        // Get the fixture object and publish it, but without relations!
        $obj = $this->objFromFixture(TestManyManyThroughDataObject::class, 'obj1');
        $obj->publishSingle();
        $id = $obj->ID;

        Versioned::set_stage(Versioned::LIVE);
        $obj = TestManyManyThroughDataObject::get()->byID($id);
        $field = SortableUploadField::create('Files', 'Files', $obj->Files())->setRecord($obj);
        $this->assertEmpty($field->getItems()->column('Title'), 'There shouldn\'t be any published records');

        // Now publish everything
        $obj->publishRecursive();
        $obj = TestManyManyThroughDataObject::get()->byID($id);
        $field = SortableUploadField::create('Files', 'Files', $obj->Files())->setRecord($obj);
        $this->assertEquals(['FileA', 'FileB', 'FileC', 'FileD'], $field->getItems()->column('Title'));

        Versioned::set_stage(Versioned::DRAFT);
        // change the sort Order and remove an Item
        $obj = TestManyManyThroughDataObject::get()->byID($id);
        $field = SortableUploadField::create('Files', 'Files', $obj->Files())->setRecord($obj);
        $field->setSubmittedValue(['Files' => ['3','2','1']]);
        $field->saveInto($obj);
        $obj->write();

        // Should now be changed on Draft
        $this->assertEquals(['FileC', 'FileB', 'FileA'], $obj->getLinkedFiles()->column('Title'));

        Versioned::set_stage(Versioned::LIVE);
        // Should still be unchanged on Live
        $obj = TestManyManyThroughDataObject::get()->byID($id);
        $this->assertEquals(['FileA', 'FileB', 'FileC', 'FileD'], $obj->getLinkedFiles()->column('Title'));

        // Should be updated after publishing
        $obj->publishRecursive();
        $obj = TestManyManyThroughDataObject::get()->byID($id);
        $this->assertEquals(['FileC', 'FileB', 'FileA'], $obj->getLinkedFiles()->column('Title'));
    }
}

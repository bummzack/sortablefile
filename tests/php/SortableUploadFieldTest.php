<?php

namespace Bummzack\SortableFile\Tests;

use Bummzack\SortableFile\Forms\SortableUploadField;
use Bummzack\SortableFile\Tests\Model\TestDataObject;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\ArrayList;
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

    public function testSchemaDefaults()
    {
        $field = SortableUploadField::create('Files');

        // Setup a dummy form, so that `getSchemaDataDefaults` doesn't error out
        Controller::config()->set('url_segment', 'dummy');
        Form::create(
            Controller::curr(),
            'TestForm',
            FieldList::create($field),
            FieldList::create(FormAction::create('test'))
        );

        $data = $field->getSchemaDataDefaults();

        $this->assertArrayHasKey('sortable', $data);
        $this->assertTrue($data['sortable']);
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

        // set field items in other order
        $field->setValue($obj->Files()->sort('FileHash'));
        // Items should be returned in the correct order though.
        $this->assertEquals(['FileA', 'FileB', 'FileC', 'FileD'], $field->getItems()->column('Title'));

        $field = SortableUploadField::create('OtherFiles', 'OtherFiles', $obj->OtherFiles())
            ->setRecord($obj)
            ->setSortColumn('Sort');

        $this->assertEquals(['FileC', 'FileB', 'FileA'], $field->getItems()->column('Title'));
    }

    public function testAddingFilesToNewRecord()
    {
        // Create a new DataObject which will have an unsaved relation
        $obj = TestDataObject::create();

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

    public function testAddingFilesWithoutFormSubmission()
    {
        // Create a new DataObject which will have an unsaved relation
        $obj = TestDataObject::create();

        $field = SortableUploadField::create('Files', 'Files', $obj->Files())->setRecord($obj);
        // Set the value from a List without sort order
        $field->setValue(ArrayList::create(
            File::get()->byIDs([1,2,3])->toArray()
        ));
        $field->saveInto($obj);
        $obj->write();

        // Items should be ordered by ID now
        $this->assertEquals(['FileA', 'FileB', 'FileC'], $obj->Files()->sort('SortOrder')->column('Title'));
        $this->assertEquals(['FileA', 'FileB', 'FileC'], $field->getItems()->column('Title'));
    }

}

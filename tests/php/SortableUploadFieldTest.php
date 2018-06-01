<?php

namespace Bummzack\SortableFile\Tests;

use Bummzack\SortableFile\Forms\SortableUploadField;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

/**
 * Test basic field functionality, such as properties etc.
 * @package Bummzack\SortableFile\Tests
 */
class SortableUploadFieldTest extends SapphireTest
{
    protected static $fixture_file = 'SortableUploadFieldTest.yml';

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
}

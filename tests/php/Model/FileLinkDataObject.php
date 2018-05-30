<?php

namespace Bummzack\SortableFile\Tests\Model;


use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class FileLinkDataObject extends DataObject
{

	private static $db = [
		'SortOrder'			=> 'Int'
	];

	private static $has_one = [
		'File'				=> File::class,
		'Owner'				=> TestDataObject::class
	];

	private static $default_sort = 'SortOrder';

	private static $extensions = [
		Versioned::class
	];

}
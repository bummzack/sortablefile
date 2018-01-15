<?php

namespace Bummzack\SortableFile\Forms;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\Sortable;


/**
 * Extension of the UploadField to add sorting of files
 *
 * @author bummzack
 * @skipUpgrade
 */
class SortableUploadField extends UploadField
{
    /**
     * @var string the column to be used for sorting
     */
    protected $sortColumn = 'SortOrder';


    public function Field($properties = [])
    {
        return parent::Field($properties);
    }

    /**
     * Set the column to be used for sorting
     * @param string $sortColumn
     * @return $this
     */
    public function setSortColumn($sortColumn)
    {
        $this->sortColumn = $sortColumn;
        return $this;
    }

    /**
     * Returns the column to be used for sorting
     * @return string
     */
    public function getSortColumn()
    {
        return $this->sortColumn;
    }

    public function getItems()
    {
        $items = parent::getItems();
        if ($items instanceof Sortable) {
            return $items->sort([$this->getSortColumn() => 'ASC', 'ID' => 'ASC']);
        }
        return $items;
    }

    public function saveInto(DataObjectInterface $record)
    {
        return parent::saveInto($record);
    }
}

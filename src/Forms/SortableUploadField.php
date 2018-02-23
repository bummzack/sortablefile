<?php

namespace Bummzack\SortableFile\Forms;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\ORM\RelationList;
use SilverStripe\ORM\Sortable;
use SilverStripe\ORM\UnsavedRelationList;


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

    protected $rawSubmittal = null;

    public function getSchemaDataDefaults()
    {
        $defaults = parent::getSchemaDataDefaults();
        $defaults['sortable'] = true;
        return $defaults;
    }

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
        parent::saveInto($record);

        // Check required relation details are available
        $fieldname = $this->getName();
        if (!$fieldname || !is_array($this->rawSubmittal)) {
            return $this;
        }

        // Check type of relation
        $relation = $record->hasMethod($fieldname) ? $record->$fieldname() : null;
        if ($relation) {
            $idList = $this->getItemIDs();
            $rawList = $this->rawSubmittal;

            if ($relation instanceof ManyManyList) {
                DB::get_conn()->withTransaction(function () use ($relation, $idList, $rawList) {
                    // TODO: Optimize by using SQL update in one batch. Only works for the existing relation though!
                    $sort = 0;
                    $relation->removeAll();
                    foreach ($rawList as $id) {
                        if (in_array($id, $idList)) {
                            $relation->add($id, [ $this->getSortColumn() => $sort++ ]);
                        }
                    }
                });
            } elseif ($relation instanceof UnsavedRelationList) {
                $sort = 0;

                $relation->removeAll();
                foreach ($rawList as $id) {
                    if (in_array($id, $idList)) {
                        $relation->add($id, [ $this->getSortColumn() => $sort++ ]);
                    }
                }
            }
        }
        return $this;
    }

    public function setSubmittedValue($value, $data = null)
    {
        // Intercept the incoming IDs since they are properly sorted
        if (is_array($value) && isset($value['Files'])) {
            $this->rawSubmittal = $value['Files'];
        }
        return $this->setValue($value, $data);
    }
}

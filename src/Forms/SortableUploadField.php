<?php

namespace Bummzack\SortableFile\Forms;

use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\ORM\Sortable;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\UnsavedRelationList;

/**
 * Extension of the UploadField to add sorting of files
 *
 * @author bummzack
 * @skipUpgrade
 */
class SortableUploadField extends UploadField
{
    private static $dependencies = [
        'logger' => '%$Psr\Log\LoggerInterface',
    ];

    /**
     * @var string the column to be used for sorting
     */
    protected $sortColumn = 'SortOrder';

    protected $rawSubmittal = null;

    /**
     * @var LoggerInterface
     */
    public $logger;

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

    /**
     * Return the files in sorted order
     * @return File[]|SS_List
     */
    public function getItems()
    {
        $items = parent::getItems();

        // An ArrayList won't contain our sort-column, thus it has to be sorted by the raw submittal data.
        // This is an issue that's seemingly exclusive to saving SiteConfig.
        if (($items instanceof ArrayList) && !empty($this->rawSubmittal)) {
            // flip the array, so that we can look up index by ID
            $sortLookup = array_flip($this->rawSubmittal);
            $itemsArray = $items->toArray();
            usort($itemsArray, function ($itemA, $itemB) use ($sortLookup) {
                if (isset($sortLookup[$itemA->ID]) && isset($sortLookup[$itemB->ID])) {
                    return $sortLookup[$itemA->ID] - $sortLookup[$itemB->ID];
                }
                return 0;
            });

            return ArrayList::create($itemsArray);
        }

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
            $sortColumn = $this->getSortColumn();

            if ($relation instanceof ManyManyList) {
                try {
                    DB::get_conn()->withTransaction(function () use ($relation, $idList, $rawList, $record, $sortColumn) {
                        $relation->getForeignID();
                        $ownerIdField = $relation->getForeignKey();
                        $fileIdField = $relation->getLocalKey();
                        $joinTable = '"'. $relation->getJoinTable() .'"';

                        $sort = 0;
                        foreach ($rawList as $id) {
                            if (in_array($id, $idList)) {
                                // Use SQLUpdate to update the data in the join-table.
                                // This is safe to do, since new records have already been written to the DB in the
                                // parent::saveInto call.
                                SQLUpdate::create($joinTable)
                                    ->setWhere([
                                        "\"$ownerIdField\" = ?" => $record->ID,
                                        "\"$fileIdField\" = ?" => $id
                                    ])
                                    ->assign($sortColumn, $sort++)
                                    ->execute();
                            }
                        }
                    });
                } catch (\Exception $ex) {
                    $this->logger->warning('Unable to sort files in sortable relation.', ['exception' => $ex]);
                }
            } elseif ($relation instanceof UnsavedRelationList) {
                // With an unsaved relation list the items can just be removed and re-added
                $sort = 0;
                $relation->removeAll();
                foreach ($rawList as $id) {
                    if (in_array($id, $idList)) {
                        $relation->add($id, [$sortColumn => $sort++]);
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

<?php

namespace Bummzack\SortableFile\Forms;

use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\ORM\ManyManyThroughQueryManipulator;
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
     * The column to be used for sorting
     * @var string
     */
    protected $sortColumn = 'SortOrder';

    /**
     * Raw submitted form data
     * @var null|array
     */
    protected $rawSubmittal = null;

    /**
     * @var LoggerInterface
     */
    public $logger;

    public function getSchemaDataDefaults()
    {
        $defaults = parent::getSchemaDataDefaults();
        // Add a sortable prop for the react component
        $defaults['sortable'] = true;
        return $defaults;
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
                    // Apply the sorting, wrapped in a transaction.
                    // If something goes wrong, the DB will not contain invalid data
                    DB::get_conn()->withTransaction(function () use ($relation, $idList, $rawList, $record, $sortColumn) {
                        $this->sortManyManyRelation($relation, $idList, $rawList, $record, $sortColumn);
                    });
                } catch (\Exception $ex) {
                    $this->logger->warning('Unable to sort files in sortable relation.', ['exception' => $ex]);
                }
            } elseif ($relation instanceof ManyManyThroughList) {
                try {
                    // Apply the sorting, wrapped in a transaction.
                    // If something goes wrong, the DB will not contain invalid data
                    DB::get_conn()->withTransaction(function () use ($relation, $idList, $rawList, $sortColumn) {
                        $this->sortManyManyThroughRelation($relation, $idList, $rawList, $sortColumn);
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

    /**
     * Apply sorting to a many_many relation
     * @param ManyManyList $relation
     * @param array $idList
     * @param array $rawList
     * @param DataObjectInterface $record
     * @param $sortColumn
     */
    protected function sortManyManyRelation(
        ManyManyList $relation,
        array $idList,
        array $rawList,
        DataObjectInterface $record,
        $sortColumn
    ) {
        $relation->getForeignID();
        $ownerIdField = $relation->getForeignKey();
        $fileIdField = $relation->getLocalKey();
        $joinTable = '"' . $relation->getJoinTable() . '"';
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
    }

    /**
     * Apply sorting to a many_many_through relation
     * @param ManyManyThroughList $relation
     * @param array $idList
     * @param array $rawList
     * @param $sortColumn
     * @throws \SilverStripe\ORM\ValidationException
     */
    protected function sortManyManyThroughRelation(
        ManyManyThroughList $relation,
        array $idList,
        array $rawList,
        $sortColumn
    ) {
        $relation->getForeignID();
        $dataQuery = $relation->dataQuery();
        $manipulators = $dataQuery->getDataQueryManipulators();
        $manyManyManipulator = null;
        foreach ($manipulators as $manipulator) {
            if ($manipulator instanceof ManyManyThroughQueryManipulator) {
                $manyManyManipulator = $manipulator;
                break;
            }
        }

        if (!$manyManyManipulator) {
            throw new \LogicException('No ManyManyThroughQueryManipulator found');
        }

        $joinClass = $manyManyManipulator->getJoinClass();
        $ownerIDField = $manyManyManipulator->getForeignKey();
        $fileIdField = $manyManyManipulator->getLocalKey();

        $sort = 0;
        foreach ($rawList as $id) {
            if (in_array($id, $idList)) {
                $fileRecord = DataList::create($joinClass)->filter([
                    $ownerIDField => $relation->getForeignID(),
                    $fileIdField  => $id
                ])->first();

                if ($fileRecord) {
                    $fileRecord->setField($sortColumn, $sort++);
                    $fileRecord->write();
                }
            }
        }
    }
}

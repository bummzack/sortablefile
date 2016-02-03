<?php

/**
 * Sortable data-extension
 * Currently this only adds the required "SortOrder" column to the DB.
 *
 * @author bummzack
 */
class Sortable extends DataExtension
{
    private static $db = array(
        'SortOrder' => 'Int'
    );

    public static $sort_dir = 'ASC';

    protected static $sortTables = array();

    /**
     * @see DataExtension::augmentSQL()
     */
    public function augmentSQL(SQLQuery &$query)
    {
        $select = $query->getSelect();
        if (
            empty($select) ||
            $query->getDelete() ||
            in_array("COUNT(*)", $select) ||
            in_array("count(*)", $select)
        ) {
            return;
        }

        if (!isset(self::$sortTables[$this->owner->class])) {
            // look up the table that has the SortOrder field
            $class = ClassInfo::table_for_object_field($this->owner->class, 'SortOrder');
            self::$sortTables[$this->owner->class] = $class;
        } else {
            $class = self::$sortTables[$this->owner->class];
        }

        if ($class) {
            $query->addOrderBy("\"$class\".\"SortOrder\" " . self::$sort_dir);
        } else {
            $query->addOrderBy("\"SortOrder\" " . self::$sort_dir);
        }
    }

    /**
     * Assign a sort number when object is written
     * @see DataExtension::onBeforeWrite()
     */
    public function onBeforeWrite()
    {
        if (!$this->owner->exists() || !$this->owner->SortOrder) {
            // get the table in the ancestry that has the SortOrder field
            $table = ClassInfo::table_for_object_field($this->owner->class, 'SortOrder');
            $sql = new SQLQuery('MAX("SortOrder")', $table);
            $val = $sql->execute()->value();
            $this->owner->SortOrder = is_numeric($val) ? $val + 1 : 1;
        }
    }
}

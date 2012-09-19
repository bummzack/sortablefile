<?php
/**
 * Sortable data-extension
 * Currently this only adds the required "Sorting" column to the DB. 
 * Automatic sorting by this column would be nice, but "augmentSQL" doesn't seem
 * to get called in the current version of SilverStripe.
 * 
 * Fix: Add `static $default_sort = "Sorting ASC";` to the exended class :(
 * 
 * @author bummzack
 */
class Sortable extends DataExtension
{
	public static $db = array(
		'Sorting' => 'Int'
	);
	
	public static $sort_dir = 'ASC';
	
	/**
	 * @see DataExtension::augmentSQL()
	 */
	public function augmentSQL(SQLQuery &$query)
	{
		$select = $query->getSelect();
		if(
			empty($select) || 
			$query->getDelete() || 
			in_array("COUNT(*)",$select) || 
			in_array("count(*)",$select)
		){ return; }
		
		$query->setOrderBy("\"Sorting\" " . self::$sort_dir);
	}
	
	/**
	 * Assign a sort number when object is written
	 * @see DataExtension::onBeforeWrite()
	 */
	public function onBeforeWrite()
	{
		if(!$this->owner->ID || !$this->owner->Sorting) {
			$classes = ClassInfo::dataClassesFor($this->owner->ClassName);
		    $sql = new SQLQuery('count(ID)', array_shift($classes));
		    $val = $sql->execute()->value();
		    $this->owner->Sorting = is_numeric($val) ? $val+1 : 1;
		    
		}
	}	
}
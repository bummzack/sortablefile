<?php
/**
 * Sortable data-extension.
 * Makes an object sortable. To make an object sortable, add
 * `Object::add_extension('MyObject', 'Sortable');` in `mysite/_config.php` 
 * 
 * @author bummzack
 */
class Sortable extends DataExtension
{
	// add the sort order to the main Object
	public static $db = array(
		'SortOrder' => 'Int'
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
		
		$query->setOrderBy("\"SortOrder\" " . self::$sort_dir);
	}
	
	/**
	 * Assign a sort number when object is written
	 * @see DataExtension::onBeforeWrite()
	 */
	public function onBeforeWrite()
	{
		if(!$this->owner->ID || !$this->owner->SortOrder) {
			$classes = ClassInfo::dataClassesFor($this->owner->ClassName);
		    $sql = new SQLQuery('count(ID)', array_shift($classes));
		    $val = $sql->execute()->value();
		    $this->owner->SortOrder = is_numeric($val) ? $val+1 : 1;
		}
	}
}
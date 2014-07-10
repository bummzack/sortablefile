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
		if(
			empty($select) || 
			$query->getDelete() || 
			in_array("COUNT(*)",$select) || 
			in_array("count(*)",$select)
		){ return; }
		
		if(!isset(self::$sortTables[$this->owner->class])){
			$classes = array_reverse(ClassInfo::dataClassesFor($this->owner->class));
			$class = null;
			foreach($classes as $cls){
				if(DataObject::has_own_table($cls) && $fields = DataObject::database_fields($cls) && isset($fields['SortOrder'])){
					$class = $cls;
					break;
				}
			}
			self::$sortTables[$this->owner->class] = $class;
		} else {
			$class = self::$sortTables[$this->owner->class];
		}
		
		if($class){
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
		if((!$this->owner->ID && !$this->owner->SortOrder) || !$this->owner->SortOrder) {
			$classes = ClassInfo::dataClassesFor($this->owner->ClassName);
		    $sql = new SQLQuery('count(ID)', array_shift($classes));
		    $val = $sql->execute()->value();
		    $this->owner->SortOrder = is_numeric($val) ? $val+1 : 1;
		}
	}	
}

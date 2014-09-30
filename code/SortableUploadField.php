<?php
/**
 * Extension of the UploadField to add sorting of files
 *
 * @author bummzack
 */
class SortableUploadField extends UploadField 
{
	/**
	 * @var string the column to be used for sorting
	 */
	protected $sortColumn = 'SortOrder';
	
	public function Field($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(SORTABLEFILE_DIR . '/javascript/SortableUploadField.js');
		Requirements::css(SORTABLEFILE_DIR . '/css/SortableUploadField.css');
		return parent::Field($properties);
	}
	
	/**
	 * @param int $itemID
	 * @return UploadField_ItemHandler
	 */
	public function getItemHandler($itemID) {
		return SortableUploadField_ItemHandler::create($this, $itemID);
	}
	
	/**
	 * Add the field to the relation and set the sort order
	 * @see UploadField::encodeFileAttributes()
	 */
	protected function encodeFileAttributes(File $file) {
		$attributes = parent::encodeFileAttributes($file);
		$sortField = $this->getSortColumn();
		
		$record = $this->getRecord();
		$relationName = $this->getName();
		
		if($record && $relationName && $list = $record->$relationName()){
			if($record->many_many($relationName) !== null){
				$list->add($file, array($sortField => $list->count() + 1));
			}
		}
		
		return $attributes;
	}
	
	
	/**
	 * Set the column to be used for sorting
	 * @param string $sortColumn
	 */
	public function setSortColumn($sortColumn) {
		$this->sortColumn = $sortColumn;
		return $this;
	}
	
	/**
	 * Returns the column to be used for sorting
	 * @return string
	 */
	public function getSortColumn() {
		return $this->sortColumn;
	}
	
	public function getItems() {
		$items = parent::getItems();
		return $items->sort($this->getSortColumn(), 'ASC');
	}
	
	public function saveInto(DataObjectInterface $record) {
		$isNew = !$record->exists();
		
		parent::saveInto($record);
		
		
		// if we're dealing with an unsaved record, we have to rebuild the relation list
		// with the proper meny_many_extraFields attributes (eg. the sort order)
		if($isNew){
			// we have to grab the raw post data as the data is in the right order there.
			// this is kind of a hack, but we simply lack the information about the client-side sorting otherwise
			if(isset($_POST[$this->name]) && isset($_POST[$this->name]['Files']) && is_array($_POST[$this->name]['Files'])){
				$idList = $_POST[$this->name]['Files'];
			} else {
				// take the ItemIDs as a fallback
				$idList = $this->getItemIDs();
			}
			
			$sortColumn = $this->getSortColumn();
			$relationName = $this->getName();
			if($relationName && $record->many_many($relationName) !== null && $list = $record->$relationName()){
				$arrayList = $list->toArray();
				foreach($arrayList as $item){
					$list->remove($item);
					$list->add($item, array($sortColumn => array_search($item->ID, $idList) + 1));
				}
			}
		}
		
	}
}

class SortableUploadField_ItemHandler extends UploadField_ItemHandler 
{
	private static $allowed_actions = array(
		'sort' => true,
		'delete' => true,
		'edit' => true,
		'EditForm' => true
	);
	
	/**
	 * Action to handle sorting of a single file
	 *
	 * @param SS_HTTPRequest $request
	 */
	public function sort(SS_HTTPRequest $request) {
		
		// Check if a new position is given
		$newPosition = $request->getVar('newPosition');
		$oldPosition = $request->getVar('oldPosition');
		if ($newPosition === ""){
			return $this->httpError(403);
		}
		
		// Check form field state
		if ($this->parent->isDisabled() || $this->parent->isReadonly()){
			return $this->httpError(403);
		}
		
		// Check item permissions
		$itemMoved = $this->getItem();
		
		if (!$itemMoved){
			return $this->httpError(404);
		}
		if (!$itemMoved->canEdit()){
			return $this->httpError(403);
		}
		
		// Only allow actions on files in the managed relation (if one exists)
		$sortColumn = $this->parent->getSortColumn();
		
		$relationName = $this->parent->getName();
		$record = $this->parent->getRecord();
		if ($record && $record->hasMethod($relationName)) {
			$list = $record->$relationName();
			$list = $list->sort($sortColumn, 'ASC');
			
			$is_many_many = $record->many_many($relationName) !== null;
			
			$i = 0;
			$newPosition = intval($newPosition);
			$oldPosition = intval($oldPosition);
			$arrayList = $list->toArray();
			$itemIsInList = false;
			
			foreach ($arrayList as $item) {
				if ($item->ID == $itemMoved->ID) {
					$sort = $newPosition;
					// flag that we found our item in the list
					$itemIsInList = true;
				} else if ($i >= $newPosition && $i < $oldPosition) {
					$sort = $i + 1;
				} else if ($i <= $newPosition && $i > $oldPosition) {
					$sort = max(0, $i - 1);
				} else {
					$sort = $i;
				}
				if ($is_many_many) {
					$list->remove($item);
					$list->add($item, array($sortColumn => $sort + 1));
				} else {
					if(!$item->exists()){ $item->write(); }
					$item->$sortColumn = $sort + 1;
					$item->write();
				}
				$i++;
			}
			
			// if the item wasn't in our list, add it now with the new sort position
			if(!$itemIsInList && $is_many_many){
				$list->add($itemMoved, array($sortColumn => $newPosition + 1));
			}
			
			Requirements::clear();
			return "1";
		}
		return $this->httpError(403);
	}
	
	/**
	 * @return string
	 */
	public function SortLink() {
		$token = $this->parent->getForm()->getSecurityToken();
		return $token->addToUrl($this->Link('sort'));
	}
}

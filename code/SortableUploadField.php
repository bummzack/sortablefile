<?php
/**
 * Extension of the UploadField to add sorting of files
 * TODO: Check if we're actually getting a valid relation with "sortable" objects!
 * 
 * @author bummzack
 */
class SortableUploadField extends UploadField
{
	public function Field($properties = array()) {
		Requirements::javascript('sortablefile/javascript/SortableUploadField.js');
		Requirements::css('sortablefile/css/SortableUploadField.css');

		return parent::Field($properties);
	}
	
	/**
	 * @param int $itemID
	 * @return UploadField_ItemHandler
	 */
	public function getItemHandler($itemID) {
		return SortableUploadField_ItemHandler::create($this, $itemID);
	}
	
}

class SortableUploadField_ItemHandler extends UploadField_ItemHandler
{
	/**
	 * Action to handle sorting of a single file
	 * 
	 * @param SS_HTTPRequest $request
	 * @return ViewableData_Customised
	 */
	public function sort(SS_HTTPRequest $request) {
		// Check if a new position is given
		if(!($newPosition = $request->getVar('newPosition'))){
			return $this->httpError(403);
		}
		
		// Check form field state
		if($this->parent->isDisabled() || $this->parent->isReadonly()) return $this->httpError(403);

		// Check item permissions
		$item = $this->getItem();
		if(!$item) return $this->httpError(404);
		if(!$item->canEdit()) return $this->httpError(403);

		// Only allow actions on files in the managed relation (if one exists)
		$items = $this->parent->getItems();
		if($this->parent->managesRelation() && !$items->byID($item->ID)) return $this->httpError(403);
		
		// get the list of attached files
		// $items seems to only contain one single entry.. therefore we need to fetch again
		$name = $this->parent->getName();
		$record = $this->parent->getRecord();
		if ($record && $record->exists()) {
			if ($record->has_many($name) || $record->many_many($name)) {
				$list = $record->{$name}();
			} else {
				return $this->httpError(403);
			}
		}
		
		$newPosition = intval($newPosition);
		
		// ensure sorting consistency across all linked files
		// this might not be the most performant way, but ensures we have
		// good sorting even when sorting was added later on (existing entries)
		$sort = 1;
		$oldPosition = $item->Sorting;
		foreach($list as $itm){
			if($itm->ID == $item->ID){
				$itm->Sorting = $newPosition;
			} else if($sort >= $newPosition && $sort < $oldPosition){
				$itm->Sorting = $sort + 1;
			} else if($sort <= $newPosition && $sort > $oldPosition){
				$itm->Sorting = max(1, $sort - 1);
			} else {
				$itm->Sorting = $sort;
			}
			$itm->write();
			$sort++;
		}
		return "1";
	}
	
	/**
	 * @return string
	 */
	public function SortLink() {
		$token = $this->parent->getForm()->getSecurityToken();
		return $token->addToUrl($this->Link('sort'));
	}
}
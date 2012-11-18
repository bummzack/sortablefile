<?php
/**
 * Extension of the UploadField to add sorting of files
 * TODO: Check if we're actually getting a valid relation with "sortable" objects!
 *
 * @author bummzack
 */
class SortableUploadField extends UploadField {
	/**
	 * @var string the column to be used for sorting
	 */
	protected $sortColumn = 'SortOrder';
	public function Field($properties = array()) {
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
	 * Set the column to be used for sorting
	 * @param string $sortColumn
	 */
	public function setSortColumn($sortColumn) {
		$this->sortColumn = $sortColumn;
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
}

class SortableUploadField_ItemHandler extends UploadField_ItemHandler {
	/**
	 * Action to handle sorting of a single file
	 *
	 * @param SS_HTTPRequest $request
	 */
	public function sort(SS_HTTPRequest $request) {
		// Check if a new position is given
		$newPosition = $request->getVar('newPosition');
		if ($newPosition === "")
			return $this->httpError(403);
		// Check form field state
		if ($this->parent->isDisabled() || $this->parent->isReadonly())
			return $this->httpError(403);
		// Check item permissions
		$itemMoved = $this->getItem();
		if (!$itemMoved)
			return $this->httpError(404);
		if (!$itemMoved->canEdit())
			return $this->httpError(403);
		// Only allow actions on files in the managed relation (if one exists)
		$sortColumn = $this->parent->getSortColumn();
		if ($this->parent->managesRelation() && !$this->parent->getItems()->byID($itemMoved->ID))
			return $this->httpError(403);
		$relationName = $this->parent->getName();
		$record = $this->parent->getRecord();
		if ($record && $record->exists() && $record->hasMethod($relationName)) {
			$list = $record->$relationName();
			$list = $list->sort($sortColumn, 'ASC');
			$many_many = ($list instanceof ManyManyList);
			if ($many_many) {
				// we need to fetch $itemMoved again from the relation if its many_many so we get the
				// SortOrder column from the relation table
				$itemMoved = $list->byID($itemMoved->ID);
				list($parentClass, $componentClass, $parentField, $componentField, $table) = $record->many_many($relationName);
			}
			$i = 0;
			$newPosition = intval($newPosition);
			$oldPosition = intval($itemMoved->$sortColumn);
			foreach ($list as $item) {
				if ($item->ID == $itemMoved->ID) {
					$sort = $newPosition;
				} else if ($i >= $newPosition && $i < $oldPosition) {
					$sort = $i + 1;
				} else if ($i <= $newPosition && $i > $oldPosition) {
					$sort = max(0, $i - 1);
				} else {
					$sort = $i;
				}
				if ($many_many) {
					$q = sprintf(
						'UPDATE "%s" SET "%s" = %d WHERE "%s" = %d AND "%s" = %d',
						$table,
						$sortColumn,
						$sort,
						$componentField,
						$item->ID,
						$parentField,
						$record->ID
					);
					DB::query($q);
				} else {
					$item->$sortColumn = $sort;
					$item->write();
				}
				$i++;
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
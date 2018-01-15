import ACTION_TYPES from './SortableUploadFieldActionTypes';

export function changeSort(oldIndex, newIndex) {
  return (dispatch) =>
    dispatch({
      type: ACTION_TYPES.SORTABLE_UPLOADFIELD_SORT,
      payload: { oldIndex, newIndex },
    });
}

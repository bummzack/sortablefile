import {arrayMove} from 'react-sortable-hoc';
import getFieldReducer from 'lib/reduxFieldReducer';
import ACTION_TYPES from './SortableUploadFieldActionTypes';

const sortableUploadFieldReducerTransformer = (originalReducer) => (getGlobalState) => (state, action) => {
  const reduceField = state ? getFieldReducer(state.uploadField, action) : null;

  switch (action.type) {
    case ACTION_TYPES.SORTABLE_UPLOADFIELD_SORT:
      return {
        ...state,
        uploadField: reduceField((field) => ({
          files: arrayMove(field.files, action.payload.oldIndex, action.payload.newIndex),
        }))
      };
    default: {
      return originalReducer(state, action);
    }
  }
};

export default sortableUploadFieldReducerTransformer;

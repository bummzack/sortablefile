import {arrayMove} from 'react-sortable-hoc';
import ACTION_TYPES from './SortableUploadFieldActionTypes';

const sortableUploadFieldReducerTransformer = (originalReducer) => (getGlobalState) => (state, { type, payload }) => {
  switch (type) {
    case ACTION_TYPES.SORTABLE_UPLOADFIELD_SORT:
      return {
        files: arrayMove(state.files, payload.oldIndex, payload.newIndex)
      };
    default: {
      return originalReducer(state, { type, payload });
    }
  }
};

export default sortableUploadFieldReducerTransformer;

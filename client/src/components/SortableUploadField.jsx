import React, {Component} from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {SortableContainer} from 'react-sortable-hoc';
import * as actions from 'state/SortableUploadFieldActions';

//TODO: Is this really needed?
const SortableList = SortableContainer((props) => {
  return props.children;
});

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      uploadField: bindActionCreators(actions, dispatch),
    },
  };
}

const enhancedUploadField = (UploadField) => {
  class SortableUploadField extends Component {
    constructor(props) {
      super(props);
      this.onSortEnd = this.onSortEnd.bind(this);
      this.cancelStartHandler = this.cancelStartHandler.bind(this);
    };

    onSortEnd({oldIndex, newIndex}) {
      this.props.actions.uploadField.changeSort(this.props.id, oldIndex, newIndex);
    };

    cancelStartHandler(e){
      if (!this.props.sortable) {
        return true;
      }

      // Cancel sorting if the event target is an `input`, `textarea`, `select` or `option`
      const disabledElements = ['input', 'textarea', 'select', 'option', 'button'];

      if (disabledElements.indexOf(e.target.tagName.toLowerCase()) !== -1) {
        return true; // Return true to cancel sorting
      }
    };

    render() {
      return (
        <SortableList items={this.props.files} lockAxis="y" onSortEnd={this.onSortEnd} shouldCancelStart={this.cancelStartHandler}>
          <UploadField {...this.props} />
        </SortableList>
      );
    }
  }
  return connect(null, mapDispatchToProps)(SortableUploadField);
};

export default enhancedUploadField;


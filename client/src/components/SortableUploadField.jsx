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
    };

    onSortEnd({oldIndex, newIndex}) {
      this.props.actions.uploadField.changeSort(this.props.id, oldIndex, newIndex);
    };

    render() {
      if (!this.props.sortable) {
        return (
          <UploadField {...this.props} />
        );
      }
      return (
        <SortableList items={this.props.files} lockAxis="y" onSortEnd={this.onSortEnd}>
          <UploadField {...this.props} />
        </SortableList>
      );
    }
  }
  return connect(null, mapDispatchToProps)(SortableUploadField);
};

export default enhancedUploadField;


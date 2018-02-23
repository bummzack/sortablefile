import React, {Component} from 'react';
import {SortableElement} from 'react-sortable-hoc';
import {connect} from "react-redux";

const SortableItem = SortableElement((props) =>
  <div className="sortable-item">{props.children}</div>
);

function mapStateToProps(state, ownprops) {
  //TODO: This is flawed on many levels. Not all forms will have that namespace
  const id = `Form_EditForm_${ownprops.name}`;
  let files = [];
  if (state.assetAdmin
    && state.assetAdmin.uploadField
    && state.assetAdmin.uploadField.fields
    && state.assetAdmin.uploadField.fields[id]
  ) {
    files = state.assetAdmin.uploadField.fields[id].files || [];
  }
  return { files };
}

const enhancedUploadFieldItem = (UploadFieldItem) => {
  class SortableUploadFieldItem extends Component {
    render() {
      const index = this.props.files.findIndex(file => this.props.item.id === file.id);
      return (
        <SortableItem index={index}>
          <UploadFieldItem {...this.props} />
        </SortableItem>
      );
    }
  }
  return connect(mapStateToProps)(SortableUploadFieldItem);
};

export default enhancedUploadFieldItem;

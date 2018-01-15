import React, {Component} from 'react';
import {SortableElement} from 'react-sortable-hoc';

const SortableItem = SortableElement((props) =>
  <div className="sortable-item">{props.children}</div>
);

const enhancedUploadFieldItem = (UploadFieldItem) => {
  return class SortableUploadFieldItem extends Component {
    render() {
      return (
        <SortableItem index={this.props.index}>
          <UploadFieldItem {...this.props} />
        </SortableItem>
      );
    }
  }
  //return inject(['UploadFieldItem'])(SortableUploadField);
};

export default enhancedUploadFieldItem;

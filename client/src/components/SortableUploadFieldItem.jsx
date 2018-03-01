import React, {Component} from 'react';
import {SortableElement, SortableHandle} from 'react-sortable-hoc';
import {connect} from "react-redux";

const DragHandle = SortableHandle(() =>
  <span className="sortable-item__handle icon font-icon-drag-handle">::</span>
);

const SortableItem = SortableElement((props) =>
  <div className="sortable-item">
    <DragHandle />
    {props.children}
  </div>
);

const enhancedUploadFieldItem = (UploadFieldItem) => (props) => {
  return (
    <SortableItem index={props.index}>
      <UploadFieldItem {...props} />
    </SortableItem>
  );
};

export default enhancedUploadFieldItem;

import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {SortableContainer} from 'react-sortable-hoc';
import enhancedUploadFieldItem from 'components/SortableUploadFieldItem';
import * as actions from 'state/SortableUploadFieldActions';
import { inject } from 'lib/Injector';

const SortableList = SortableContainer((props) => {
  return props.children;
}, {withRef: true});

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
      this.container = null;
      this.onSortEnd = this.onSortEnd.bind(this);
      this.getItemProps = this.getItemProps.bind(this);
      this.getContainer = this.getContainer.bind(this);
      this.DecoratedUploadFieldItem = enhancedUploadFieldItem(props.UploadFieldItem);
      this.cancelStartHandler = this.cancelStartHandler.bind(this);
    }

    getItemProps(props, index)
    {
      return {
        ...props,
        index
      };
    }

    getContainer()
    {
      if (this.container) {
        return this.container;
      }
      let el = ReactDOM.findDOMNode(this);
      while ((el = el.parentElement) && !el.classList.contains("panel--scrollable"));
      this.container = el;
      return el;
    }

    onSortEnd({oldIndex, newIndex}) {
      this.props.actions.uploadField.changeSort(this.props.id, oldIndex, newIndex);
    }

    cancelStartHandler(e){
      if (!this.props.sortable) {
        return true;
      }

      // Cancel sorting if the event target is an `input`, `textarea`, `select` or `option`
      const disabledElements = ['input', 'textarea', 'select', 'option', 'button'];

      if (disabledElements.indexOf(e.target.tagName.toLowerCase()) !== -1) {
        return true; // Return true to cancel sorting
      }
    }

    render() {
      if (!this.props.sortable) {
        return <UploadField {...this.props} />
      }

      const newProps = {
        ...this.props,
        UploadFieldItem: this.DecoratedUploadFieldItem,
        getItemProps: this.getItemProps
      };

      return (
        <SortableList
          items={this.props.files}
          lockAxis="y"
          onSortEnd={this.onSortEnd}
          useDragHandle={true}
          getContainer={this.getContainer}
          shouldCancelStart={this.cancelStartHandler}
          helperClass="sortable-item--dragging"
        >
          <UploadField {...newProps} />
        </SortableList>
      )
    }
  }

  return connect(null, mapDispatchToProps)(
    inject(['UploadFieldItem'])(SortableUploadField)
  )
};

export default enhancedUploadField;


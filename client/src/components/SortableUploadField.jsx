import React, {Component} from 'react';
import {SortableContainer} from 'react-sortable-hoc';

const SortableList = SortableContainer((props) => {
  return props.children;
});

const enhancedUploadField = (UploadField) => {
  return class SortableUploadField extends Component {
    constructor(props) {
      super(props);
      this.state = {
        sortedFiles: props.files || []
      };
      this.onSortEnd = this.onSortEnd.bind(this);
    };

    componentWillReceiveProps(nextProps) {
      if (nextProps.files !== this.props.files) {
        this.setState({
          sortedFiles: nextProps.files
        })
      }
    }

    onSortEnd({oldIndex, newIndex}) {
      if (this.state.sortedFiles) {
        this.setState({
          sortedFiles: arrayMove(this.state.sortedFiles, oldIndex, newIndex),
        });
      }
    };

    render() {
      const newProps = {...this.props, files: this.state.sortedFiles};
      return (
        <SortableList items={this.state.sortedFiles} onSortEnd={this.onSortEnd}>
          <UploadField {...newProps} />
        </SortableList>
      );
    }
  };
};

export default enhancedUploadField;


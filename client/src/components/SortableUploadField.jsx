import React, {Component} from 'react';

const enhancedUploadField = (UploadField) => {
  return class SortableUploadField extends Component {
    render() {
      return (
        <div className="dummy">
          HAHAHA
          <UploadField {...this.props} />
        </div>
      );
    }
  }
};

export default enhancedUploadField;

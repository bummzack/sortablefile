/* global document */
import React from 'react';
import Injector from 'lib/Injector';
import SortableUploadField from 'components/SortableUploadField';
import SortableUploadFieldItem from 'components/SortableUploadFieldItem';
import sortableUploadFieldReducerTransformer from 'state/SortableUploadFieldReducerTransformer';

document.addEventListener('DOMContentLoaded', () => {
  Injector.transform(
    'enhance-uploadfield',
    (updater) => {
      updater.component('UploadField', SortableUploadField, 'SortableUploadField');
      updater.reducer('assetAdmin', sortableUploadFieldReducerTransformer);
    }
  );
});

/* global document */
import React from 'react';
import Injector from 'lib/Injector';
import SortableUploadField from 'components/SortableUploadField';
import SortableUploadFieldItem from 'components/SortableUploadFieldItem';

document.addEventListener('DOMContentLoaded', () => {
  Injector.transform(
    'enhance-uploadfield',
    (updater) => {
      updater.component('UploadField', SortableUploadField, 'SortableUploadField');
      updater.component('UploadFieldItem', SortableUploadFieldItem, 'SortableUploadFieldItem');
    }
  );
});

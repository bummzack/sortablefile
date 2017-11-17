/* global document */
import Injector from 'lib/Injector';
import SortableUploadField from 'components/SortableUploadField';

document.addEventListener('DOMContentLoaded', () => {
  Injector.transform(
    'enhance-uploadfield',
    (updater) => {
      updater.component('UploadField', SortableUploadField, 'SortableUploadField');
    }
  );
});

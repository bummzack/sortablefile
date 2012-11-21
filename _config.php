<?php
/**
 * Simple extension to the UploadField class that allows sorting of multiple files.
 * 
 * Works with has_many and many_many relations.
 */

define('SORTABLEFILE_DIR', basename(dirname(__FILE__)));
define('SORTABLEFILE_PATH', BASE_PATH . '/' . SORTABLEFILE_DIR);

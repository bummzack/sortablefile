<?php

namespace Bummzack\SortableFile\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

class LeftAndMainExtension extends Extension
{
    public function init()
    {
        Requirements::javascript('bummzack/sortablefile: client/dist/js/main.js');
        Requirements::css('bummzack/sortablefile: client/dist/styles/main.css');
    }
}

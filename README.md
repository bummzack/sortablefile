sortablefile
============

An extension for SilverStripe 3.0 that allows sorting of multiple attached images (extends UploadField).

This currently only works for `has_many` relations. For `many_many` relations, have a look at the `many_many` branch.


Installation
------------

Clone/download this repository into a folder called "sortablefile" in your SilverStripe installation folder. Run `dev/build` afterwards.

Example setup
-------------

Let's assume we have a `PortfolioPage` that has multiple `PortfolioImages`. The `PortfolioImage` is a subclass of `Image` and looks like this:

    class PortfolioImage extends Image
    {
        public static $has_one = array(
            'PortfolioPage' => 'PortfolioPage'
        );
    }


We enable sorting for `PortfolioImage` by adding the following line to `mysite/_config.php` (run `dev/build` afterwards!):

    // Make portfolio images sortable
    Object::add_extension('PortfolioImage', 'Sortable');

The `PortfolioPage` looks like this:

    class PortfolioPage extends Page
    {   
        public static $has_many = array(
            'Images' => 'PortfolioImage'
        );
    
        public function getCMSFields()
        {
            $fields = parent::getCMSFields();
        
            // Use SortableUploadField instead of UploadField!
            $imageField = new SortableUploadField('Images', 'Portfolio images');
        
            $fields->addFieldToTab('Root.Images', $imageField);
            return $fields;
        }
    }

Once this has been set up like described above, then you should be able to add images in the CMS and sort them by dragging them (use the thumbnail as handle).

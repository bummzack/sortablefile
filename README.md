sortablefile
============

An extension for SilverStripe 3.0 that allows sorting of multiple attached images (extends UploadField).

This is meant to be used with a `many_many` relation. The `many_many` relation should be preferred over the `has_many` relation,
as it will allow you to add the same image/file to multiple pages and have individual sorting for each page.

Upgrading
------------

**Warning:** This release is incompatible with the previous release of `sortablefile`. If you use the `Sortable` DataExtension, 
you'll have to remove references to it from your `_config.php`, since this class has become obsolete. Eg.

    Object::add_extension('MyImageClass', 'Sortable'); // <-- Remove these lines!
    
After switching from the `has_many` version of this module to the `many_many` one, you'll have to re-sort existing images as the sort-order won't transfer over.

Installation
------------

Clone/download this repository into a folder called "sortablefile" in your SilverStripe installation folder. Run `dev/build` afterwards.

Example setup
-------------

Let's assume we have a `PortfolioPage` that has multiple `Images` attached. 
First create an extension that will allow adding Images to several pages. We name it `LinkedImage`.

    class LinkedImage extends DataExtension
    {
        // this image belongs to many pages. We use Page here, so that the image can be added to any page
        public static $belongs_many_many = array(
            'Pages' => 'Page'   
        );
        
        // The default sorting of the image. This is needed so that your images appear in the correct
        // order in the frontend
        public static $default_sort = 'SortOrder ASC';
    }


We enable the above extension by adding the following line to `mysite/_config.php` (run `dev/build` afterwards!):

    // Make images attachable to (multiple) pages
    Object::add_extension('Image', 'LinkedImage');

The `PortfolioPage` looks like this:

    class PortfolioPage extends Page
    {   
        // This page can have many images
        public static $many_many = array(
            'Images' => 'Image'
        );
        
        // this adds the SortOrder field to the relation table. Please note that the key (in this case 'Images') 
        // has to be the same key as in the $many_many definition!
        public static $many_many_extraFields = array(
            'Images' => array('SortOrder' => 'Int')
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

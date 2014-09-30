sortablefile
============

An extension for SilverStripe 3.1 that allows sorting of multiple attached images (extends UploadField).

This is meant to be used with a `many_many` or `has_many` relation. The `many_many` relation should be preferred over the `has_many` relation, as it will allow you to add the same image/file to multiple pages and have individual sorting for images on each page.

Installation
------------
The easiest way is to use [composer](https://getcomposer.org/):

    composer require bummzack/sortablefile 1.0.*@dev

Alternatively, clone/download this repository into a folder called "sortablefile" in your SilverStripe installation folder. 

Run `dev/build` afterwards.

Example setup for many_many
-------------

Let's assume we have a `PortfolioPage` that has multiple `Images` attached. 

The `PortfolioPage` looks like this:

```php
class PortfolioPage extends Page
{   
    // This page can have many images
    private static $many_many = array(
        'Images' => 'Image'
    );
    
    // this adds the SortOrder field to the relation table. 
    // Please note that the key (in this case 'Images') 
    // has to be the same key as in the $many_many definition!
    private static $many_many_extraFields = array(
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
    
    // Use this in your templates to get the correctly sorted images
    // OR use $Images.Sort('SortOrder') in your templates which 
    // will unclutter your PHP classes
    public function SortedImages(){
        return $this->Images()->Sort('SortOrder');
    }
}
```

Once this has been set up like described above, then you should be able to add images in the CMS and sort them by dragging them (use the thumbnail as handle).

Example setup for has_many
-------------

As mentioned previously, a `many_many` relation is usually the better choice for Page &rarr; File relations. If you still want a `has_many` relation, here's a way to do it.

Let's assume we have a `PortfolioPage` that has multiple `Images`. To achieve that we create a `DataExtension` that looks like this:

```php
class PortfolioImage extends DataExtension
{
    private static $has_one = array(
        'PortfolioPage' => 'PortfolioPage'
    );
}
```

We enable the `PortfolioImage` extension by adding the following line to `mysite/_config/config.yml` (run `dev/build` afterwards):

```yml
# put this in your mysite/_config/config.yml
Image:
  extensions:
    - PortfolioImage
    - Sortable
```


The `PortfolioPage` looks like this:

```php
class PortfolioPage extends Page
{   
    private static $has_many = array(
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
```

Once this has been set up like described above, you should be able to add images in the CMS and sort them by dragging them (use the thumbnail as handle).

Templates
-------------

Sorting the Files via a relation table isn't easily achievable via a DataExtension. This is why it's currently up to the user to implement a getter that will return the sorted files, something along the lines of:

```php
// Use this in your templates to get the correctly sorted images
public function SortedImages(){
    return $this->Images()->Sort('SortOrder');
}
```

And then in your templates use: 

```html+smarty
<% loop SortedImages %>
$SetWidth(500)
<% end_loop %>
```

Alternatively, you could simply use the sort statement in your template, which will remove the need for a special getter method in your page class.

```html+smarty
<% loop Images.Sort('SortOrder') %>
$SetWidth(500)
<% end_loop %>
```

The above is only true for `many_many` relations. All `has_many` relations will be sorted automatically and you can just use:

```html+smarty
<% loop Images %>
$SetWidth(500)
<% end_loop %>
```
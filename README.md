sortablefile
============

An extension for SilverStripe 4.1+ that allows sorting of multiple attached images.

This is meant to be used with a `many_many` relation.

Installation
------------
The easiest way is to use [composer](https://getcomposer.org/):

    composer require bummzack/sortablefile ^2@dev
    
Run `dev/build` afterwards.

Example setup for many_many
-------------

Let's assume we have a `PortfolioPage` that has multiple `Images` attached. 

The `PortfolioPage` looks like this:

```php

use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;

class PortfolioPage extends Page
{   
    // This page can have many images
    private static $many_many = [
        'Images' => Image::class
    ];
    
    // this adds the SortOrder field to the relation table. 
    // Please note that the key (in this case 'Images') 
    // has to be the same key as in the $many_many definition!
    private static $many_many_extraFields = [
        'Images' => ['SortOrder' => 'Int']
    ];

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields){
            $fields->addFieldToTab('Root.Main', SortableUploadField::create('Images'));
        });
        return parent::getCMSFields();
    }
}
```

Once this has been set up like described above, then you should be able to add images in the CMS 
and sort them by dragging them (use the thumbnail as handle).

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

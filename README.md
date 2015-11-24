#Silverstripe Dropzone Sortable#
This plugin allows drag/drop sorting via `FileAttachmentField` from the dropzone package.

# Installation Instructions #
## Composer ##
Run the following to add this module as a requirement and install it via composer.

```
composer require "webfox/silverstripe-dropzone-sortable"
```

# Setup #

## Setting up the Relation ##
We need a `SortOrder` column on the Image/File relation that `FileAttachmentField` is hooked onto.  
For a has_many to a custom `DataObject` simply add the `'SortOrder' => 'int'` and `private static $default_sort = 'SortOrder';` to the DataObject.

If you are relating to `Image` or `File` directly then you will need a `many_many` setup with `many_many_extrafields` and an accessor to loop over in the template  
`<% loop $SortedImages>...<% end_loop %>` or you can do `<% loop $Images.Sort('SortOrder') %>...<% end_loop %>`.
 This package will automatically sort the files in the FileAttachmentField.
 
 Here is an example `many_many` setup:

````PHP
class MyPage extends Page {
    
    private static $many_many = [
        'Images' => 'Image'
    ];

    private static $many_many_extraFields = [
        'Images' => ['SortOrder' => 'Int']
    ];
    
    public function getSortedImages(){
        return $this->Images()->sort('SortOrder');
    }
}
````

## Enabling Sorting ##
To enable sorting simply call `->sortable()` on the `FileAttachmentField` e.g. `FileAttachmentField::create('Images')->sortable()->imagesOnly();`

## Customization ##
The only customization available is changing the sort column. `FileAttachmentField::create('Images')->sortable()->setSortableColumn('OtherSortColumn');`
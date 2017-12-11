Options overview
================

## Required options

* `mapping`: The mapping id configured in `app/config/config.yml`

## Optional options

* `js_callback`: A string representing a javaScript function name for a callback after an upload. `null` by default.
* `js_image_callback`: A boolean which determine the default callback after an upload. Set it to true if you want a preview for your pictures. `false` by default.
* `multiple`: A boolean that allow multiple files upload. `false` by default. 
* `remove_uri_path`: A string representing an url for removing files. It can be useful if you want to overide the default controller. `null` by default.
* `upload_uri_path`: A string representing an url for the file upload. It can be useful if you want to overide the default controller. `null` by default.

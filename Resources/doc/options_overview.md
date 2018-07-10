Options overview
================

## Required options

* `mapping`: The mapping id configured in `app/config/config.yml`
* `upload_mode`: One of : "immediate", "temporary". Immediate mode will update the entity as soon as the upload request
  is over, whereas temporary mode will store the file on the server, waiting for the full form to be submitted.
  This lets the user change his mind without updating the object too quickly. The temporary mode requires an entity
  implementing TemporaryUploadedFileInterface

## Optional options

* `js_callback`: A string representing a JavaScript function name for a callback after an upload. `null` by default.
* `image_preview`: A boolean which determine the default callback after an upload. Set it to true if you want a preview for your pictures. `false` by default.
* `multiple`: A boolean that allow multiple files upload. `false` by default. 
* `remove_uri_path`: The url for removing files. It can be useful if you want to override the default controller.
  Default value is the route `sherlockode_afb_remove`.
* `remove_tmp_uri_path`: The url for removing temporary files from temporary mode. Default value is the route `sherlockode_afb_remove_tmp`.
* `upload_uri_path`: The url for the AJAX file upload.
  Default value is the route `sherlockode_afb_upload` (or `sherlockode_afb_upload_tmp` for temporary mode).

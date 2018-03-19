(function($) {
    $.fn.AfbAjaxUploader = function(){

        return this.each(function(){
            var container = $(this),
                uploadCallback = container.data('callback'),
                isImgPreview = container.data('imgpreview'),
                uploadMode = container.data('uploadMode'),
                isMultiple = container.data('multiple'),
                uploadUrl = container.data('uploadurl'),
                removeUrl = container.data('removeurl'),
                mapping = container.data('mapping'),
                formName = container.data('name'),
                subjectId = container.data('id'),
                formPrefix = container.closest('form').attr('name'),
                uploadCounter = (container.find('.afb_item').length > 0) ? container.find('.afb_item').length + 1 : 0;

            function onXhrFail(jqXhr){
                if (jqXhr.status >= 400 && jqXhr.status < 500) {
                    alert(jqXhr.responseText);
                } else {
                    alert('An error happened');
                }
            }

            function onDropFile(event) {
                var files = event.dataTransfer.files;
                if (!isMultiple) {
                    if (files.length > 1) {
                        return alert('You can only upload one file');
                    } else {
                        container.find('.afb_dropzone').hide();
                    }
                }
                for (var i = 0; i < files.length; i++) {
                    uploadFile(files[i]);
                }
            }

            function uploadFile(file){
                var formData = new FormData(),
                    xhr = new window.XMLHttpRequest(),
                    uploadId = uploadCounter;
                uploadCounter++;
                formData.append('afb_upload_temp_file[file]', file);
                if (mapping) {
                    formData.append('afb_upload_temp_file[mapping]', mapping);
                }
                if (subjectId) {
                    if (isMultiple) {
                        // ...
                    } else {
                        formData.append('afb_upload_temp_file[id]', subjectId);
                    }
                }
                filePreview(uploadId, file);
                if (uploadMode === 'none') {
                    return;
                }

                $.ajax({
                    xhr: function() {
                        xhr.upload.addEventListener("progress", function(evt) {
                            var progression = (evt.loaded * 100) / evt.total;
                            var listItem = container.find('.afb_upload_container .afb_preview_' + uploadId);
                            listItem.find('.afb_file_progress > div').css('width', progression + '%');
                        });
                        return xhr;
                    },
                    url: uploadUrl,
                    type: 'POST',
                    contentType: false,
                    processData: false,
                    data: formData
                }).done(function(response) {
                    if (uploadCallback && "function" === typeof(window[uploadCallback])) {
                        window[uploadCallback].call(null, response);
                    }
                    if (uploadMode === 'temporary') {
                        addHiddenFields(uploadId, response);
                    }
                }).fail(onXhrFail);
            }

            function filePreview(uploadId, file){
                if (isImgPreview && file.type.match('image.*')) {
                    var reader = new FileReader();
                    reader.onload =
                        function(e) {
                            var item = $(
                                '<li class="afb_item afb_preview_item afb_preview_' + uploadId + '" data-upload="' + uploadId + '">' +
                                '<div class="afb_file_preview">' +
                                '<img src="' + e.target.result + '" />' +
                                '</div>' +
                                '<div class="afb_filename upload-details">' + file.name + '</div>' +
                                '<div class="afb_file_progress upload-details"><div></div></div>' +
                                '<a class="afb_remove_file upload-details" href="#" data-upload="' + uploadId + '">X</a>' +
                                '</li>'
                            );
                            container.find('.afb_upload_container').append(item);
                        };
                    reader.readAsDataURL(file);
                } else {
                    var item = $(
                        '<li class="afb_item afb_preview_' + uploadId + '" data-upload="' + uploadId + '">' +
                        '<div class="afb_filename upload-details">' + file.name + '</div>' +
                        '<div class="afb_file_progress upload-details"><div></div></div>' +
                        '<a class="afb_remove_file upload-details" href="#" data-upload="' + uploadId + '">X</a>' +
                        '</li>'
                    );
                    container.find('.afb_upload_container').append(item);
                }
            }

            function addHiddenFields(index, data){
                var fields = [];
                if (isMultiple) {
                    fields.push({name: formPrefix + '[' + formName + '][files][' + index + '][pathname]', value: data.pathname});
                    fields.push({name: formPrefix + '[' + formName + '][files][' + index + '][size]', value: data.size});
                    fields.push({name: formPrefix + '[' + formName + '][files][' + index + '][mime-type]', value: data['mime-type']});
                } else {
                    fields.push({name: formPrefix + '[' + formName + '][files][pathname]', value: data.pathname});
                    fields.push({name: formPrefix + '[' + formName + '][files][size]', value: data.size});
                    fields.push({name: formPrefix + '[' + formName + '][files][mime-type]', value: data['mime-type']});
                }
                for (var i = 0; i < fields.length; i++) {
                    container.append($('<input class="afb_upload_' + index + '" type="hidden" name="' + fields[i].name + '" value="' + fields[i].value + '">'));
                }
            }

            function deletePreview(element){
                var uploadId = element.data('upload');
                container.find('li.afb_preview_' + uploadId).remove();
                container.find('.afb_upload_' + uploadId).remove();
                if (!isMultiple && container.find('.afb_item').length <= 0) {
                    container.find('.afb_dropzone').show();
                }
                if (isMultiple) {
                    var pictureId = element.data('id');
                    if (pictureId) {
                        removeFile(pictureId);
                    }
                } else {
                    removeFile(subjectId);
                }
            }

            function removeFile(pictureId){
                var formData = new FormData();
                formData.append('afb_remove_file[mapping]', mapping);
                if (pictureId) {
                    formData.append('afb_remove_file[id]', pictureId);
                }
                if (isMultiple) {
                    formData.append('afb_remove_file[remove]', 1);
                }
                $.ajax({
                    url: removeUrl,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false
                }).fail(onXhrFail);
            }

            container.find('.afb_dropzone').each(function(index, holder) {
                holder.ondragover = function () { $(this).addClass('hover'); return false; };
                holder.ondragleave = function () { $(this).removeClass('hover'); return false; };
                holder.ondragend = function () { $(this).removeClass('hover'); return false; };
                holder.ondrop = function(event) {
                    event.preventDefault();
                    $(this).removeClass('hover');
                    onDropFile(event);
                    return false;
                };
            });

            container.find('.afb_upload_container').on('click', '.afb_remove_file', function(event) {
                event.preventDefault();
                deletePreview($(this));
            });
        });
    };

    $('.afb_file_container').AfbAjaxUploader();
}(jQuery));



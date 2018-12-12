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
                removeTmpUrl = container.data('removetmpurl'),
                mapping = container.data('mapping'),
                formName = container.data('name'),
                subjectId = container.data('id'),
                formPrefix = container.closest('form').attr('name'),
                uploadCounter = container.find('.afb_item').length > 0 ? container.find('.afb_item').length + 1 : 0,
                prototype = container.data('prototype');

            function onXhrFail(jqXhr){
                if (jqXhr.status >= 400 && jqXhr.status < 500) {
                    alert(jqXhr.responseText);
                } else {
                    alert('An error happened');
                }
            }

            function onDropFile(event) {
                var files = event.dataTransfer.files;
                onSelectFiles(files);
            }
            function onSelectFiles(files) {
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
                formData.append('afb_upload_file[file]', file);
                if (mapping) {
                    formData.append('afb_upload_file[mapping]', mapping);
                }
                if (subjectId) {
                    formData.append('afb_upload_file[id]', subjectId);
                }
                filePreview(uploadId, file);

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
                    if (response.path && isImgPreview && file.type.match('image.*')) {
                        $('.afb_preview_' + uploadId).find('img').attr('src', response.path);
                    }
                    if (response.id) {
                        $('.afb_preview_' + uploadId).find('.afb_remove_file').data('id', response.id);
                    }
                    if (uploadMode === 'temporary') {
                        addHiddenFields($('.afb_preview_' + uploadId), response);
                    }
                }).fail(onXhrFail);
            }

            function filePreview(uploadId, file){
                var item = prototype.replace(/__UPLOAD_ID__/g, uploadId)
                        .replace(/__FILE_NAME__/g, file.name);
                item = $(item);
                if (uploadMode === 'temporary') {
                    item.data('tmp', 1);
                }

                if (isImgPreview && file.type.match('image.*')) {
                    var reader = new FileReader();
                    reader.onload =
                        function(e) {
                            item = item.replace(/__FILE_URL__/g, e.target.result);
                            container.find('.afb_upload_container').append(item);
                        };
                    reader.readAsDataURL(file);
                } else {
                    container.find('.afb_upload_container').append(item);
                }
            }

            // add hidden fields for temporary upload mode
            function addHiddenFields(itemContainer, data) {
                var index = itemContainer.data('upload');
                var fields = [];
                if (isMultiple) {
                    fields.push({name: formPrefix + '[' + formName + '][files][' + index + '][key]', value: data.key});
                    fields.push({name: formPrefix + '[' + formName + '][files][' + index + '][token]', value: data.token});
                } else {
                    fields.push({name: formPrefix + '[' + formName + '][files][key]', value: data.key});
                    fields.push({name: formPrefix + '[' + formName + '][files][token]', value: data.token});
                }
                for (var i = 0; i < fields.length; i++) {
                    itemContainer.append($('<input class="afb_upload_' + index + '" type="hidden" name="' + fields[i].name + '" value="' + fields[i].value + '">'));
                }
            }

            function deletePreview(element){
                if (isMultiple) {
                    var pictureId = element.data('id');
                    if (pictureId) {
                        removeFile(pictureId);
                    }
                } else if (element.closest('.afb_item').data('tmp')) {
                    removeFile(element.closest('.afb_item').find('[name$=\\[token\\]]').val(), true);
                } else {
                    removeFile(subjectId, false);
                }
                element.closest('.afb_item').remove();
                if (container.find('.afb_item').length === 0) {
                    container.find('.afb_dropzone').show();
                }
            }

            function removeFile(id, isTmp){
                var formData = new FormData();
                var url = removeUrl;
                if (!isTmp) {
                    formData.append('afb_remove_file[mapping]', mapping);
                    formData.append('afb_remove_file[id]', id);
                } else {
                    formData.append('token', id);
                    url = removeTmpUrl;
                }
                if (isMultiple) {
                    formData.append('afb_remove_file[remove]', 1);
                }
                $.ajax({
                    url: url,
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
            container.find('.afb_file_input').on('change', function () {
                onSelectFiles(this.files);
            });
            container.find('.afb_upload_container').on('click', '.afb_remove_file', function(event) {
                event.preventDefault();
                deletePreview($(this));
            });
        });
    };
}(jQuery));

jQuery(function ($) {
    $('.afb_file_container').AfbAjaxUploader();
});

(function() {
'use strict';
let jQuery;

if (typeof module === "object" && module.exports) {
    jQuery = require("jquery");
} else {
    jQuery = window.jQuery;
}

let $ = jQuery;
    $.fn.AfbAjaxUploader = function(options) {
        let settings = $.extend({
            onXhrFail: baseOnXhrFail,
            uploadCallback: null,
            onPreRemove: null,
        }, options);

        function baseOnXhrFail(jqXhr){
            let text = 'An error occurred';
            if (jqXhr.status >= 400 && jqXhr.status < 500) {
                if (jqXhr.responseJSON && jqXhr.responseJSON.error) {
                    text = jqXhr.responseJSON.error;
                } else {
                    text = jqXhr.responseText;
                }
            }
            alert(text);
        }

        return this.each(function(){
            var container = $(this),
                uploadCallback = container.data('callback') ? container.data('callback') : settings.uploadCallback,
                preRemoveCallback = container.data('preRemoveCallback'),
                ajaxErrorCallback = container.data('errorcallback'),
                isImgPreview = container.data('imgpreview'),
                uploadMode = container.data('uploadMode'),
                isMultiple = container.data('multiple'),
                uploadUrl = container.data('uploadurl'),
                removeUrl = container.data('removeurl'),
                removeTmpUrl = container.data('removetmpurl'),
                mapping = container.data('mapping'),
                formName = container.data('name'),
                subjectId = container.data('id'),
                uploadCounter = container.find('.afb_item').length > 0 ? container.find('.afb_item').length + 1 : 0,
                prototype = container.data('prototype'),
                isAsync = container.data('async');

            let onXhrFail = ajaxErrorCallback && "function" === typeof(window[ajaxErrorCallback]) ? window[ajaxErrorCallback] : settings.onXhrFail;
            let onPreRemove = preRemoveCallback && "function" === typeof(window[preRemoveCallback]) ? window[preRemoveCallback] : settings.onPreRemove;

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
                if (isAsync) {
                    for (var i = 0; i < files.length; i++) {
                        uploadFile(files[i]);
                    }
                } else {

                    var next = function (i) {
                        if ("undefined" === typeof(files[i])) {
                            return;
                        }
                        uploadFile(files[i], function () {
                            next(i + 1);
                        });
                    };
                    next(0);
                }
            }

            function uploadFile(file, callback){
                let dz = container.find('.afb_dropzone');
                if (dz.length && !dz.hasClass('afb_dropzone-started')) {
                    dz.addClass('afb_dropzone-started');
                }

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

                            listItem.addClass('afb_upload_progressing');
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
                    let  previewElement = container.find('.afb_preview_' + uploadId);

                    previewElement.addClass('afb_upload_complete');
                    previewElement.removeClass('afb_upload_progressing');

                    if (response.id) {
                        previewElement.find('.afb_remove_file').data('id', response.id);
                    }
                    if (uploadMode === 'temporary') {
                        addHiddenFields(previewElement, response);
                    }
                    var processUploadedFileFunction = function() {
                        previewElement.find('.afb_file_progress').addClass('afb_file_upload_success');
                        if (response.path && isImgPreview && file.type.match('image.*')) {
                            previewElement.find('img').attr('src', response.path);
                        }
                    };
                    if (uploadCallback && "function" === typeof(window[uploadCallback])) {
                        window[uploadCallback].call(null, response, previewElement, processUploadedFileFunction);
                    } else {
                        processUploadedFileFunction();
                    }
                    if ("function" === typeof(callback)) {
                        callback();
                    }
                }).fail(function(response) {
                    onXhrFail(response);
                    var previewElement = $('.afb_preview_' + uploadId);
                    previewElement.find('.afb_file_progress').addClass('afb_file_upload_error');
                    deletePreview(previewElement.find('.afb_remove_file'), false);
                });
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
                            var img = item.find('.afb_file_preview img');
                            if (0 === img.length) {
                                img = $(document.createElement('img'));
                                item.find('.afb_file_preview').append(img);
                            }
                            img.attr('src', e.target.result);
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
                    fields.push({name: formName + '[files][' + index + '][key]', value: data.key});
                    fields.push({name: formName + '[files][' + index + '][token]', value: data.token});
                } else {
                    fields.push({name: formName + '[files][key]', value: data.key});
                    fields.push({name: formName + '[files][token]', value: data.token});
                }
                for (var i = 0; i < fields.length; i++) {
                    itemContainer.append($('<input class="afb_upload_' + index + '" type="hidden" name="' + fields[i].name + '" value="' + fields[i].value + '">'));
                }
            }

            function deletePreview(element, removeFromServer){
                if (removeFromServer) {
                    let isTmp = 1 === element.closest('.afb_item').data('tmp'),
                        elId = subjectId;

                    if (isTmp) {
                        elId = element.closest('.afb_item').find('[name$=\\[token\\]]').val();
                    } else if (isMultiple) {
                        elId = element.data('id');
                    }

                    if (elId) {
                        removeFile(elId, isTmp, function() { deleteFile(element) });
                        return;
                    }
                }

                deleteFile(element);
            }

            function deleteFile(element) {
                element.closest('.afb_item').remove();
                if (container.find('.afb_item').length === 0) {
                    container.find('.afb_dropzone').removeClass('afb_dropzone-started');

                    if (!isMultiple) {
                        container.find('.afb_dropzone').show();
                    }
                }
            }

            function triggerRemove(element, next) {
                if (onPreRemove && "function" === typeof(onPreRemove)) {
                    onPreRemove.call(null, element, function() {
                        return next();
                    });
                } else {
                    return next();
                }
            }

            function removeFile(id, isTmp, callback){
                var formData = new FormData();
                var url = isTmp ? removeTmpUrl : removeUrl;
                if (!isTmp) {
                    formData.append('afb_remove_file[mapping]', mapping);
                    formData.append('afb_remove_file[id]', id);
                } else {
                    formData.append('token', id);
                }
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false
                }).done(function() {
                    callback();
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
                let element = $(this);
                triggerRemove(element, function() {
                    return deletePreview(element, true);
                });
            });
        });
    };
})();

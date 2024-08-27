/*
 * EpigtorImage plugin
 *
 */

+function ($) { "use strict";

    // EPIGTOR CLASS DEFINITION
    // ============================

    var EpigtorImage = function(element, options) {
        var that       = this
        this.options   = options
        this.$el       = $(element)

        this.requestHandler = this.$el.data('handler');
        this.requestHandlerCancel = this.$el.data('handler-cancel');
        this.requestHandlerDelete = this.$el.data('handler-delete');
        this.requestHandlerTitle = this.$el.data('handler-title');
        this.imagePartial = this.$el.data('image-partial');
        this.refreshCode = this.$el.data('refresh-code');
        this.csrfToken = this.$el.data('csrf-token');
        this.editMessage = this.$el.data('message');
        this.editModel = {'model': this.$el.data('model-class'), 'id': this.$el.data('model-id')}
        this.imageId = this.$el.data('image-id');
        this.imageContent = this.$el.data('image-content') || {
            id: '',
            pathUrl: '',
            thumbUrl: '',
            title: '',
            description: '',
            file_size: '',
            file_name: ''
        };
        this.uploadHandler = this.$el.data('upload-handler');
        this.labelDelete = this.$el.data('label-delete');
        this.showDelete = this.$el.data('show-delete');
        this.labelSave = this.$el.data('label-save');
        this.labelCancel = this.$el.data('label-cancel');
        this.labelUpload = this.$el.data('label-upload');
        this.labelReplace = this.$el.data('label-replace');
        this.labelImageTitle = this.$el.data('label-image-title');
        this.elementId = this.$el.data('element-id');

        this.$imageContainer = $(epigtorImageModalHtml(this.imageContent, this.editModel, this.editMessage, this.elementId, this.uploadHandler,
                this.showDelete, this.labelDelete, this.labelSave, this.labelCancel, this.labelUpload, this.labelReplace, this.labelImageTitle));
        $(document.body).append(this.$imageContainer);

        epigtorDragElement(this.$imageContainer[0]);

        this.$imageWidget = this.$imageContainer.find('.epigtor-image-widget:first');
        this.$imageWidget.fileUploader();

        this.$save = this.$imageContainer.find('.epigtor-image-save:first');
        this.$cancel = this.$imageContainer.find('.epigtor-image-cancel:first');
        this.$delete = this.$imageContainer.find('.epigtor-image-delete:first');

        this.$imageTextForm = this.$imageContainer.find('.epigtor-image-title:first');
        this.$imageText = this.$imageTextForm.find('input:first');
        this.originalTitle = this.$imageText.val();

        var $preview = $(this.$imageWidget.find('.upload-object'));
        this.originalFileName = $('.info .filename span', $preview).text();
        this.originalSize = $('.info .size', $preview).text();

        this.$controlPanel = $('<div />').addClass('control-epigtor')
        this.$edit = $('<button />').addClass('epigtor-edit-button').text('Edit').appendTo(this.$controlPanel)
        $(document.body).append(this.$controlPanel)

        this.$el.on('mousemove', function(){
            if (epigtorIsEditing) {
                that.refreshControlPanel()
            }
        })

        this.showControlPanel()

        this.$edit.on('click', function(){
            that.showImageWidget();
            hideControlPanels();
        })

        this.labelDeleteConfirm = this.$el.data('label-delete-confirm');

        var $fileUpload = this.$imageWidget.data('oc.fileUpload');
        // disable update button when file is uploaded
        $fileUpload.dropzone.on('addedfile', $fileUpload.proxy(this.onUploadStart));
        $fileUpload.dropzone.on('success', $fileUpload.proxy(this.onUploadSuccess));

        // remove onclick event from image preview (set in fileupload.js)
        this.$imageWidget.off('click', '.upload-object.is-success .file-data-container-inner', $fileUpload.proxy($fileUpload.onClickSuccessObject));
        this.$imageWidget.off('click', '.toolbar-clear-file', $fileUpload.proxy($fileUpload.onClearFileClick));

        this.$save.on('click', function(){ that.clickSave() });
        this.$cancel.on('click', function(){ that.clickCancel() });
        this.$delete.on('click', function(){ that.clickDelete() });
    }

    EpigtorImage.DEFAULTS = {
        option: 'default'
    }

    EpigtorImage.prototype.hideControlPanel = function() {
        this.$controlPanel.removeClass('visible');
    }

    EpigtorImage.prototype.refreshControlPanel = function() {
        if (!this.$controlPanel.hasClass('visible')) {
            this.showControlPanel();
        }

        this.$controlPanel
            .width(this.$el.outerWidth())
            .height(this.$el.outerHeight())
            .css({
                top: this.$el.offset().top,
                left: this.$el.offset().left + this.$el.outerWidth() - this.$controlPanel.outerWidth()
            });
    }

    EpigtorImage.prototype.showControlPanel = function() {
        this.$controlPanel.addClass('visible');
        if (!this.$controlPanel.hasClass('active')) {
            this.refreshControlPanel();
        }
    }

    EpigtorImage.prototype.onUploadStart = function(file) {
        var epigtorImage = $('#epigtor-image-'+this.$el.attr('id')).data('oc.epigtorImage');
        epigtorImage.$save.prop('disabled', true);
    }

    EpigtorImage.prototype.onUploadSuccess = function(file, response) {
        var epigtorImage = $('#epigtor-image-'+this.$el.attr('id')).data('oc.epigtorImage');
        epigtorImage.changed = true;
        // $(epigtorImage.$save).show();
        $.request(epigtorImage.requestHandler, {
            data: {
                message: epigtorImage.editMessage,
                imageId: response.id,
                model: epigtorImage.editModel,
                imagePartial: epigtorImage.imagePartial,
                save: 0,
            },
            complete: function() {
                eval(epigtorImage.refreshCode);
                epigtorImage.$save.prop('disabled', false);
            }
        })
    }

    EpigtorImage.prototype.clickCancel = function() {
        console.log('cancel');

        this.$imageText.val(this.originalTitle);

        if (this.changed) {
            console.log('deleting file in server');
            var $preview = $(this.$imageWidget.find('.upload-object'));
            var imageId = $preview.data('id');
            var that = this;
            $.request(this.requestHandlerCancel, {
                data: {
                    message: this.editMessage,
                    imageId: imageId,
                    model: this.editModel,
                    imagePartial: this.imagePartial,
                },
                complete: function(response) {
                    console.log(response);

                    $preview.addClass('is-success');
            
                    if (response.image) {
                        var content = response.image;
                        $preview.data('id', content.id);
                        $preview.data('path', content.pathUrl);
                        var $img = $('.image img', $preview);
                        $img.attr('src', content.thumbUrl);
                        $('.info .filename span', $preview).text(that.originalFileName);
                        $('.info .size', $preview).text(that.originalSize);
                    } else {
                        that.$imageWidget.data('oc.fileUpload').dropzone.removeAllFiles()
                        that.$imageWidget.data('oc.fileUpload').evalIsPopulated();
                        $('.epigtor-image-empty').show();
                    }

                    that.hideImageWidget();
                    eval(that.refreshCode);
                }
            })
        } else {
            this.hideImageWidget();
        }
    }

    EpigtorImage.prototype.clickSave = function() {
        console.log('save');
        if (this.changed) {
            var $preview = $(this.$imageWidget.find('.upload-object'));
            var imageId = $preview.data('id');
            var that = this;
            $.request(this.requestHandler, {
                data: {
                    message: this.editMessage,
                    imageId: imageId,
                    model: this.editModel,
                    imagePartial: this.imagePartial,
                    save: 1,
                },
                complete: function(response) {
                    that.imageId = response.image.id;
                    that.saveTitle();
                    that.$delete.show();
                    that.hideImageWidget();
                    eval(that.refreshCode);
                    var $preview = $(that.$imageWidget.find('.upload-object'));
                    that.originalFileName = $('.info .filename span', $preview).text();
                    that.originalSize = $('.info .size', $preview).text();
                }
            });
        } else {
            this.saveTitle();
            this.hideImageWidget();
        }
    }

    EpigtorImage.prototype.saveTitle = function() {
        if (!this.imageId) return;

        var newTitle = this.$imageText.val();
        if (newTitle == this.originalTitle) return;

        var that = this;
        $.request(this.requestHandlerTitle, {
            data: {
                fileId: that.imageId,
                title: newTitle,
            },
            complete: function() {
                that.originalTitle = newTitle;
            }
        });
    }

    EpigtorImage.prototype.clickDelete = function() {
        console.log('delete image');
        var that = this;

        if (confirm(this.labelDeleteConfirm)) {
            $.request(this.requestHandlerDelete, {
                data: {
                    message: this.editMessage,
                    model: this.editModel,
                    imageId: this.imageId,
                    imagePartial: this.imagePartial,
                }
            }).done(function() {
                that.imageId = '';
                that.originalTitle = '';
                that.$imageWidget.data('oc.fileUpload').dropzone.removeAllFiles()
                that.$imageWidget.data('oc.fileUpload').evalIsPopulated();
                that.$imageText.val(that.originalTitle);
                $('.epigtor-image-empty').show();
                that.hideImageWidget();
            });
        }
    }

    EpigtorImage.prototype.hideImageWidget = function() {
        this.$imageContainer.removeClass('visible');
        this.refreshControlPanel()
        this.$controlPanel.removeClass('active')
        this.$edit.show()
    }

    EpigtorImage.prototype.showImageWidget = function() {
        // this.$save.hide();
        if (!this.imageId) this.$delete.hide();
        this.$imageContainer.css({
            top: this.$el.offset().top + this.$el.outerHeight(),
            left: this.$el.offset().left
        });
        this.changed = false;
        this.$imageContainer.addClass('visible');
        this.refreshControlPanel();
        this.$controlPanel.addClass('active');
        this.$edit.hide();
        // var $fileUpload = this.$imageWidget.data('oc.fileUpload');
        // $fileUpload.dropzone.hiddenFileInput.click();
    }

    // EPIGTOR PLUGIN DEFINITION
    // ============================

    var old = $.fn.epigtorImage

    $.fn.epigtorImage = function () {
        return this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.epigtorImage')
            var options = $.extend({}, EpigtorImage.DEFAULTS, $this.data())
            if (!data) {
                $this.data('oc.epigtorImage', (data = new EpigtorImage(this, options)))
            };
        })
    }

    $.fn.epigtorImage.Constructor = EpigtorImage

    // EPIGTOR NO CONFLICT
    // =================

    $.fn.epigtorImage.noConflict = function () {
        $.fn.epigtorImage = old
        return this
    }

    // EPIGTOR DATA-API
    // ===============

    $(document).on('click', '.epigtor-image-edit', function() {
        $(this).parent('[data-control="epigtor-image"]').epigtorImage();
    });

    $(document).on('mouseenter', '[data-control="epigtor-image"]', function() {
        if (epigtorIsEditing) {
            $(this).epigtorImage();
        }
    });

    $(document.body).append($(uploadTemplateHtml()));
    $(document.body).append($(uploadTemplateErrorHtml()));

}(window.jQuery);

function epigtorImageModalHtml(content, model, modelField, elementId, uploadHandler,
        showDelete, labelDelete, labelSave, labelCancel, labelUpload, labelReplace, labelImageTitle) {
    let fileHtml = `
    <div class="server-file"
        data-id="${content.id}"+
        data-path="${content.pathUrl}"
        data-thumb="${content.thumbUrl}"
        data-name="${content.title ? content.title : content.file_name}"
        data-description="${content.description}"
        data-size="${content.file_size}"
        data-accepted="true"
    ></div>
    `;

    let deleteButton = `
    <button type="button" class="epigtor-image-delete backend-toolbar-button control-button toolbar-clear-file populated-only">
        <i class="octo-icon-delete"></i>
        <span class="button-label">${labelDelete}</span>
    </button>
    `;

    return `
    <div class="epigtor-image-container epig-popup modal-content epigtor-image epigtor">
        <div class="modal-body">
            <form>
                <input type="hidden" name="model_class" value="${model.model}">
                <input type="hidden" name="model_id" value="${model.id}">
                <input type="hidden" name="message" value="${modelField}">
                <div
                    id="${elementId}"
                    class="epigtor-image-widget field-fileupload style-image-single is-populated dz-max-files-reached form-group"
                    data-control="fileupload"
                    data-upload-handler="${uploadHandler}"
                    data-template="#epigtor-image-template"
                    data-error-template="#epigtor-image-error-template"
                    data-unique-id="${elementId}"
                >
                    <div class="uploader-control-container">
                        <div class="uploader-control-toolbar">
                            <button type="button" class="backend-toolbar-button control-button toolbar-upload-button">
                                <i class="octo-icon-save"></i>
                                <span class="button-label" data-upload-label="${labelUpload}" data-replace-label="${labelReplace}">Upload</span>
                            </button>
                            ${showDelete ? deleteButton : ''}
                        </div>
                        <div class="upload-files-container">
                            ${content.id ? fileHtml : ''}
                        </div>
                    </div>
                </div>
            </form>
            <div class="epigtor-image-title">
                <div class="form-group">
                    <label for="image_title">${labelImageTitle}:</label>
                    <input type="text" class="form-control" name="image_title" value="${content.title ? content.title : ''}">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="epigtor-image-save btn btn-primary">${labelSave}</button>
            <button type="button" class="epigtor-image-cancel btn btn-secondary">${labelCancel}</button>
        </div>
    </div>
    `;
}

function uploadTemplateHtml() {
    return `
    <script type="text/template" id="epigtor-image-template">
        <div class="upload-object upload-object-image dz-preview dz-file-preview">
            <div class="file-data-container">
                <div class="file-data-container-inner">
                    <div class="icon-container image">
                        <img data-dz-thumbnail style="width: 190px;max-height: 200px;" alt="" />
                    </div>
                    <div class="info">
                        <h4 class="filename">
                            <span data-dz-name></span>
                        </h4>
                        <p class="description" data-description></p>
                        <p class="size" data-dz-size></p>
                    </div>
                    <div class="meta">
                        <div class="progress-bar"><span class="upload-progress" data-dz-uploadprogress></span></div>
                        <div class="error-message"><span data-dz-errormessage></span></div>
                    </div>
                </div>
            </div>
        </div>
    </script>
    `;
}

function uploadTemplateErrorHtml() {
    return `
    <script type="text/template" id="epigtor-image-error-template">
        <div class="popover-head">
            <h3>Error</h3>
            <p>Error</p>
            <button type="button" class="close" data-dismiss="popover" aria-hidden="true">&times;</button>
        </div>
        <div class="popover-body">
            <button class="btn btn-secondary" data-remove-file>Delete</button>
        </div>
    </script>
    `;
}
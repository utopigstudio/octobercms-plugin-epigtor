/*
 * Epigtor plugin
 *
 */

+function ($) { "use strict";

    // EPIGTOR CLASS DEFINITION
    // ============================

    var EpigtorRicheditor = function(element, options) {
        var self       = this
        this.options   = options
        this.$el       = $(element)

        this.originalHtml = null;
        this.richEditor = null;
        this.requestHandler = this.$el.data('handler');
        this.uploadHandler = this.$el.data('upload-handler');
        this.csrfToken = this.$el.data('csrf-token');
        this.editMessage = this.$el.data('message');
        this.editModel = null;
        if (this.$el.data('model') && this.$el.data('id')) {
            this.editModel = {'model': this.$el.data('model'), 'id': this.$el.data('id')}
        }

        this.$controlPanel = $('<div />').addClass('control-epigtor')
        this.$edit = $('<button />').addClass('epigtor-edit-button').text('Edit').appendTo(this.$controlPanel)
        this.$save = $('<button />').addClass('epigtor-save-button').text('Save').hide().appendTo(this.$controlPanel)
        this.$cancel = $('<button />').addClass('epigtor-cancel-button').text('Cancel').hide().appendTo(this.$controlPanel)

        this.toolbarButtons = this.$el.data('toolbar-buttons');

        $(document.body).append(this.$controlPanel)

        this.$el.on('mousemove', function(){
            if (epigtorIsEditing) {
                self.refreshControlPanel()
            }
        })

        self.showControlPanel()

        this.$edit.on('click', function(){ self.clickEdit() })
        this.$save.on('click', function(){ self.clickSave() })
        this.$cancel.on('click', function(){ self.clickCancel() })
    }

    EpigtorRicheditor.DEFAULTS = {
        option: 'default'
    }

    EpigtorRicheditor.prototype.clickCancel = function() {
        this.richEditor.data('oc.richEditor').setContent(this.originalHtml);
        this.richEditor.data('oc.richEditor').dispose();
        this.richEditor.find('>div>textarea:first').hide();
        this.$el.find('>.rendered').show();
        this.refreshControlPanel()
        this.$controlPanel.removeClass('active')
        this.$edit.show()
        this.$save.hide()
        this.$cancel.hide()
    }

    EpigtorRicheditor.prototype.clickSave = function() {
        var html =this.richEditor.data('oc.richEditor').getContent();
        this.richEditor.data('oc.richEditor').dispose();
        this.$el.find('>.epigtor-richeditor:first')[0].dataset.control = '';
        this.richEditor.find('>div>textarea:first').hide();
        this.$el.find('>.rendered').html(html);
        this.$el.find('>.unrendered').html(html);
        this.$el.find('>.rendered').show();
        this.refreshControlPanel()
        this.$controlPanel.removeClass('active')
        this.$edit.show()
        this.$save.hide()
        this.$cancel.hide()
        $.request(this.requestHandler, {
            data: {
                message: this.editMessage,
                content: html,
                model: this.editModel,
                type: 'richeditor',
            }
        })
    }

    EpigtorRicheditor.prototype.clickEdit = function() {
        this.originalHtml = this.$el.find('>.unrendered').html();

        this.$el.find('>.epigtor-richeditor:first')[0].dataset.control = 'richeditor';

        this.richEditor = this.$el.find('>.epigtor-richeditor:first').richEditor({
            toolbarButtons: this.toolbarButtons,
        });

        var richEditorOpts = this.richEditor.data('oc.richEditor').editor.opts;

        // Ensure that October recognizes AJAX requests from Froala
        richEditorOpts.requestHeaders = {
            'X-CSRF-TOKEN': this.csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }

        richEditorOpts.imageUploadURL = richEditorOpts.fileUploadURL = window.location;
        richEditorOpts.imageUploadParam = richEditorOpts.fileUploadParam = 'file_data';
        richEditorOpts.imageUploadParams = richEditorOpts.fileUploadParams = {
            _handler: this.uploadHandler,
            X_OCTOBER_MEDIA_MANAGER_QUICK_UPLOAD: 1
        };

        this.$el.find('>.rendered').hide();

        this.refreshControlPanel();
        this.$controlPanel.addClass('active');
        this.$save.show();
        this.$cancel.show();
        this.$edit.hide();

        hideControlPanels();
    }

    EpigtorRicheditor.prototype.hideControlPanel = function() {
        this.$controlPanel.removeClass('visible');
    }

    EpigtorRicheditor.prototype.refreshControlPanel = function() {
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

    EpigtorRicheditor.prototype.showControlPanel = function() {
        this.$controlPanel.addClass('visible');
        if (!this.$controlPanel.hasClass('active')) {
            this.refreshControlPanel();
        }
    }

    // EPIGTOR PLUGIN DEFINITION
    // ============================

    var old = $.fn.epigtorRicheditor

    $.fn.epigtorRicheditor = function () {
        return this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.epigtorRicheditor')
            var options = $.extend({}, EpigtorRicheditor.DEFAULTS, $this.data())
            if (!data) $this.data('oc.epigtorRicheditor', (data = new EpigtorRicheditor(this, options)));
        })
    }

    $.fn.epigtorRicheditor.Constructor = EpigtorRicheditor

    // EPIGTOR NO CONFLICT
    // =================

    $.fn.epigtorRicheditor.noConflict = function () {
        $.fn.epigtorRicheditor = old
        return this
    }

    // EPIGTOR DATA-API
    // ===============

    $(document).on('mouseenter', '[data-control="epigtor-richeditor"]', function() {
        if (epigtorIsEditing) {
            $(this).epigtorRicheditor();
        }
    });

}(window.jQuery);

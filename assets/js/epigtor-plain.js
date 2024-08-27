/*
 * Epigtor plugin
 *
 */

+function ($) { "use strict";

    // EPIGTOR CLASS DEFINITION
    // ============================

    var Epigtor = function(element, options) {
        var self       = this
        this.options   = options
        this.$el       = $(element)

        this.originalHtml = null;
        this.requestHandler = this.$el.data('handler');
        this.editMessage = this.$el.data('message');
        this.editModel = null;
        if (this.$el.data('model') && this.$el.data('id')) {
            this.editModel = {'model': this.$el.data('model'), 'id': this.$el.data('id')}
        }

        this.$controlPanel = $('<div />').addClass('control-epigtor')
        this.$edit = $('<button />').addClass('epigtor-edit-button').text('Edit').appendTo(this.$controlPanel)
        this.$save = $('<button />').addClass('epigtor-save-button').text('Save').hide().appendTo(this.$controlPanel)
        this.$cancel = $('<button />').addClass('epigtor-cancel-button').text('Cancel').hide().appendTo(this.$controlPanel)

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

    Epigtor.DEFAULTS = {
        option: 'default'
    }

    Epigtor.prototype.clickCancel = function() {
        this.$el.redactor('code.set', this.originalHtml)
        this.$el.redactor('core.destroy')
        this.$el.html(this.originalHtml);
        this.refreshControlPanel()
        this.$controlPanel.removeClass('active')
        this.$edit.show()
        this.$save.hide()
        this.$cancel.hide()
    }

    Epigtor.prototype.clickSave = function() {
        var html = this.$el.redactor('code.get')
        this.$el.redactor('core.destroy')
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
                type: 'plain',
            }
        })
    }

    Epigtor.prototype.clickEdit = function() {
        this.originalHtml = this.$el.html();

        this.$el.redactor({
            focus: true,
            toolbar: false,
            paragraphize: false,
            linebreaks: true
        });

        this.refreshControlPanel();
        this.$controlPanel.addClass('active');
        this.$save.show();
        this.$cancel.show();
        this.$edit.hide();

        hideControlPanels();
    }

    Epigtor.prototype.hideControlPanel = function() {
        this.$controlPanel.removeClass('visible');
    }

    Epigtor.prototype.refreshControlPanel = function() {
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

    Epigtor.prototype.showControlPanel = function() {
        this.$controlPanel.addClass('visible');
        if (!this.$controlPanel.hasClass('active')) {
            this.refreshControlPanel();
        }
    }

    // EPIGTOR PLUGIN DEFINITION
    // ============================

    var old = $.fn.epigtor

    $.fn.epigtor = function () {
        return this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.epigtor')
            var options = $.extend({}, Epigtor.DEFAULTS, $this.data())
            if (!data) $this.data('oc.epigtor', (data = new Epigtor(this, options)));
        })
    }

    $.fn.epigtor.Constructor = Epigtor

    // EPIGTOR NO CONFLICT
    // =================

    $.fn.epigtor.noConflict = function () {
        $.fn.epigtor = old
        return this
    }

    // EPIGTOR DATA-API
    // ===============

    $(document).on('mouseenter', '[data-control="epigtor"]', function() {
        if (epigtorIsEditing) {
            $(this).epigtor();
        }
    });

    $(document).on('click','.redactor-editor',function(e){
        e.preventDefault();
        e.stopPropagation();
        return false;
    });

}(window.jQuery);

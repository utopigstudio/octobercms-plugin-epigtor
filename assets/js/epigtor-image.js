/*
 * EpigtorImage plugin
 *
 */

+function ($) { "use strict";

    // EPIGTOR CLASS DEFINITION
    // ============================

    var EpigtorImage = function(element, options) {
        var self       = this
        this.options   = options
        this.$el       = $(element)

        this.requestHandlerRefresh = this.$el.data('handler-refresh');
        this.imagePartial = this.$el.data('image-partial');
        this.refreshCode = this.$el.data('refresh-code');
        this.editMessage = this.$el.data('message');
        this.editModel = {'model': this.$el.data('model-class'), 'id': this.$el.data('model-id')}
        this.imageId = this.$el.data('image-id');
        this.instanceId = this.$el.data('instance-id');
        this.popupUrl = this.$el.data('popup-url');

        this.$controlPanel = $('<div />').addClass('control-epigtor')
        this.$edit = $('<button />').addClass('epigtor-edit-button').text('Edit').appendTo(this.$controlPanel)
        $(document.body).append(this.$controlPanel)

        this.$el.on('mousemove', function(){
            if (epigtorIsEditing) {
                self.refreshControlPanel()
            }
        })

        this.showControlPanel()

        this.$edit.on('click', function(){ self.clickEdit() })
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

    EpigtorImage.prototype.clickEdit = function() {
        if (!this.popupUrl) {
            return;
        }

        window.open(this.popupUrl, '_blank', 'width=900,height=760,resizable=yes,scrollbars=yes');
    }

    EpigtorImage.prototype.refreshImage = function(imageId) {
        var self = this;

        $.request(this.requestHandlerRefresh, {
            data: {
                message: this.editMessage,
                model: this.editModel,
                imagePartial: this.imagePartial
            },
            complete: function() {
                self.imageId = imageId || '';
                if (self.refreshCode) {
                    eval(self.refreshCode);
                }
            }
        });
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

    if (!window.epigtorImageBackendListener) {
        window.epigtorImageBackendListener = true;

        window.addEventListener('message', function(event) {
            if (event.origin !== window.location.origin) {
                return;
            }

            if (!event.data || event.data.type !== 'epigtor-image-saved') {
                return;
            }

            var instanceId = event.data.instanceId;
            if (!instanceId) {
                return;
            }

            var $target = $('[data-control="epigtor-image"][data-instance-id="' + instanceId + '"]').first();
            if (!$target.length) {
                return;
            }

            var plugin = $target.data('oc.epigtorImage');
            if (!plugin) {
                $target.epigtorImage();
                plugin = $target.data('oc.epigtorImage');
            }

            if (plugin) {
                plugin.refreshImage(event.data.imageId || null);
            }
        });
    }

}(window.jQuery);

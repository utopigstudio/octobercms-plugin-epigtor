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

        self.showControlPanel()

        this.$edit.on('click', function(){ self.clickEdit() })
    }

    EpigtorRicheditor.DEFAULTS = {
        option: 'default'
    }

    EpigtorRicheditor.prototype.clickEdit = function() {
        if (!this.popupUrl) {
            return;
        }

        window.open(this.popupUrl, '_blank', 'width=1200,height=900,resizable=yes,scrollbars=yes');
    }

    EpigtorRicheditor.prototype.clickCancel = function() {
        this.$controlPanel.removeClass('active')
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

    if (!window.epigtorRicheditorBackendListener) {
        window.epigtorRicheditorBackendListener = true;

        window.addEventListener('message', function(event) {
            if (event.origin !== window.location.origin) {
                return;
            }

            if (!event.data || event.data.type !== 'epigtor-richeditor-saved') {
                return;
            }

            var instanceId = event.data.instanceId;
            var content = event.data.content;

            if (!instanceId) {
                return;
            }

            var $target = $('[data-control="epigtor-richeditor"][data-instance-id="' + instanceId + '"]').first();
            if (!$target.length) {
                return;
            }

            $target.find('>.rendered').html(content);
            $target.find('>.unrendered').html(content);
        });
    }

}(window.jQuery);

/*
 * EpigtorLink plugin
 *
 */

+function ($) { "use strict";

    // EPIGTOR CLASS DEFINITION
    // ============================

    var EpigtorLink = function(element, options) {
        var that       = this
        this.options   = options
        this.$el       = $(element)

        this.requestHandler = this.$el.data('handler');
        this.requestHandlerDelete = this.$el.data('handler-delete');
        this.requestHandlerTypeOptions = this.$el.data('handler-type-options');
        this.requestHandlerReferenceOptions = this.$el.data('handler-reference-options');
        this.linkPartial = this.$el.data('link-partial');
        this.csrfToken = this.$el.data('csrf-token');
        this.editMessage = this.$el.data('message');
        this.editModel = {'model': this.$el.data('model-class'), 'id': this.$el.data('model-id')}
        this.linkId = this.$el.data('link-id');
        this.linkContent = this.$el.data('link-content') || {
            'text': '',
            'type': '',
            'external_url': '',
            'reference': '',
            'is_new_tab': false
        };
        this.showDelete = this.$el.data('show-delete');
        this.labelDelete = this.$el.data('label-delete');
        this.labelSave = this.$el.data('label-save');
        this.labelCancel = this.$el.data('label-cancel');
        this.labelLinkText = this.$el.data('label-link-text');
        this.labelLinkType = this.$el.data('label-link-type');
        this.labelLinkUrl = this.$el.data('label-link-url');
        this.labelLinkReference = this.$el.data('label-link-reference');
        this.labelLinkIsNewTab = this.$el.data('label-link-is-new-tab');
        this.elementId = this.$el.data('element-id');
        this.cssClass = this.$el.data('css-class');

        this.$linkContainer = $(epigtorLinkModalHtml(this.linkContent, this.elementId,
            this.showDelete, this.labelDelete, this.labelSave, this.labelCancel, this.labelLinkText, this.labelLinkType, this.labelLinkUrl,
            this.labelLinkReference, this.labelLinkIsNewTab));
        $(document.body).append(this.$linkContainer);

        epigtorDragElement(this.$linkContainer[0]);

        this.$save = this.$linkContainer.find('.epigtor-link-save:first');
        this.$cancel = this.$linkContainer.find('.epigtor-link-cancel:first');
        this.$delete = this.$linkContainer.find('.epigtor-link-delete:first');
        this.$linkText = this.$linkContainer.find('input[name=text]');
        this.$linkType = this.$linkContainer.find('select[name=type]');
        this.$linkExternalUrl = this.$linkContainer.find('input[name=external_url]');
        this.$linkReference = this.$linkContainer.find('select[name=reference]');
        this.$linkIsNewTab = this.$linkContainer.find('input[name=is_new_tab]');
        this.originalText = this.$linkText.val();
        this.originalType = this.$linkContainer.find('input[name=type]').val();
        this.originalExternalUrl = this.$linkExternalUrl.val();
        this.originalReference = this.$linkContainer.find('input[name=reference]').val();
        this.originalIsNewTab = this.$linkIsNewTab.prop('checked');

        this.$controlPanel = $('<div />').addClass('control-epigtor')
        this.$edit = $('<button />').addClass('epigtor-edit-button').text('Edit').appendTo(this.$controlPanel)
        $(document.body).append(this.$controlPanel)

        this.$el.on('mousemove', function(){
            if (epigtorIsEditing) {
                that.refreshControlPanel()
            }
        })

        this.showControlPanel()

        this.$edit.on('click', function(){ that.showLinkWidget() })

        this.getTypeOptions();

        this.labelCreate = this.$el.data('label-create');
        this.labelDeleteConfirm = this.$el.data('label-delete-confirm');

        this.$save.on('click', function(){ that.clickSave() });
        this.$cancel.on('click', function(){ that.clickCancel() });
        this.$delete.on('click', function(){ that.clickDelete() });

        this.$linkType.on('change', function(){ that.typeChange() });

        this.$linkType.select2();
    }

    EpigtorLink.DEFAULTS = {
        option: 'default'
    }

    EpigtorLink.prototype.hideControlPanel = function() {
        this.$controlPanel.removeClass('visible');
    }

    EpigtorLink.prototype.refreshControlPanel = function() {
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

    EpigtorLink.prototype.showControlPanel = function() {
        this.$controlPanel.addClass('visible');
        if (!this.$controlPanel.hasClass('active')) {
            this.refreshControlPanel();
        }
    }

    EpigtorLink.prototype.clickCancel = function() {
        this.$linkText.val(this.originalText);
        this.hideLinkWidget();
    }

    EpigtorLink.prototype.clickSave = function() {
        console.log('save link');
        var that = this;
        var newText = this.$linkText.val();
        var newType = this.$linkType.val();
        var newExternalUrl = this.$linkExternalUrl.val();
        var newReference = this.$linkReference.val();
        var newIsNewTab = this.$linkIsNewTab.prop('checked');
        var hasError = false;

        this.$linkText.parent().removeClass('has-error');
        this.$linkExternalUrl.parent().removeClass('has-error');

        if (!newText) {
            this.$linkText.parent().addClass('has-error');
            hasError = true;
        }
        if (newType == 'url' && !newExternalUrl) {
            this.$linkExternalUrl.parent().addClass('has-error');
            hasError = true;
        }
        if (hasError) {
            return;
        }

        $.request(this.requestHandler, {
            data: {
                message: this.editMessage,
                model: this.editModel,
                linkId: this.linkId,
                linkPartial: this.linkPartial,
                text: newText,
                type: newType,
                external_url: newExternalUrl,
                reference: newReference,
                is_new_tab: newIsNewTab ? 1 : 0,
                cssClass: this.cssClass,
            },
            complete: function(response) {
                that.originalText = newText;
                that.originalType = newType;
                that.originalExternalUrl = newExternalUrl;
                that.originalReference = newReference;
                that.originalIsNewTab = newIsNewTab;
                that.linkId = response.link.id;
                that.$delete.show();

                that.hideLinkWidget();
            }
        })
    }

    EpigtorLink.prototype.clickDelete = function() {
        console.log('delete link');
        var that = this;

        if (confirm(this.labelDeleteConfirm)) {
            $.request(this.requestHandlerDelete, {
                data: {
                    message: this.editMessage,
                    model: this.editModel,
                    linkId: this.linkId,
                    linkPartial: this.linkPartial,
                }
            }).done(function() {
                that.linkId = '';
                that.originalText = '';
                that.$linkText.val('');
                that.originalType = 'url';
                that.$linkType.val('url');
                that.originalExternalUrl = '';
                that.$linkExternalUrl.val('');
                that.$linkExternalUrl.parent().show();
                that.originalReference = '';
                that.$linkReference.empty();
                that.$linkReference.parent().hide();
                that.originalIsNewTab = false;

                $('.epigtor-link-empty').show();
                that.hideLinkWidget();
            });
        }
    }

    EpigtorLink.prototype.hideLinkWidget = function() {
        this.$linkContainer.removeClass('visible');
        this.refreshControlPanel()
        this.$controlPanel.removeClass('active')
        this.$edit.show()
    }

    EpigtorLink.prototype.showLinkWidget = function() {
        if (!this.linkId) this.$delete.hide();
        this.$linkContainer.css({
            top: this.$el.offset().top + this.$el.outerHeight(),
            left: this.$el.offset().left
        });
        this.$linkText.parent().removeClass('has-error');
        this.$linkExternalUrl.parent().removeClass('has-error');
        this.$linkContainer.addClass('visible');
        this.refreshControlPanel();
        this.$controlPanel.addClass('active');
        this.$edit.hide();
    }

    EpigtorLink.prototype.getTypeOptions = function() {
        var that = this;
        $.request(this.requestHandlerTypeOptions, {
            complete: function(response) {
                let options = response.options;
                Object.keys(options).forEach(key => {
                    let option = new Option(options[key], key);
                    if (that.originalType == key) {
                        option.setAttribute('selected', true);
                    }
                    that.$linkType.append(option);
                })
                if (that.originalType == '') {
                    that.$linkType.val('url');
                }
                that.$linkType.trigger('change');
            }
        });
    }

    EpigtorLink.prototype.typeChange = function() {
        console.log('type change');
        console.log(this.$linkType.val());
        if (this.$linkType.val() == 'url') {
            this.$linkExternalUrl.parent().show();
            this.$linkReference.parent().hide();
        } else {
            this.$linkExternalUrl.parent().hide();
            this.$linkReference.parent().show();
            this.getReferences();
            this.$linkReference.select2({
                'width': '100%',
            });
        }
    }

    EpigtorLink.prototype.getReferences = function() {
        var that = this;
        this.$linkReference.empty();
        $.request(this.requestHandlerReferenceOptions, {
            data: {
                type: that.$linkType.val()
            },
            complete: function(response) {
                let options = response.options;
                Object.keys(options).forEach(key => {
                    let option = new Option(options[key], key);
                    if (that.originalReference == key) {
                        option.setAttribute('selected', true);
                    }
                    that.$linkReference.append(option);
                })
            }
        });
    }


    // EPIGTOR PLUGIN DEFINITION
    // ============================

    var old = $.fn.epigtorLink

    $.fn.epigtorLink = function () {
        return this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.epigtorLink')
            var options = $.extend({}, EpigtorLink.DEFAULTS, $this.data())
            if (!data) {
                $this.data('oc.epigtorLink', (data = new EpigtorLink(this, options)))
            };
        })
    }

    $.fn.epigtorLink.Constructor = EpigtorLink

    // EPIGTOR NO CONFLICT
    // =================

    $.fn.epigtorLink.noConflict = function () {
        $.fn.epigtorLink = old
        return this
    }

    // EPIGTOR DATA-API
    // ===============

    $(document).on('click', '.js--epigtor-link-edit', function() {
        $(this).parent('[data-control="epigtor-link"]').epigtorLink();
    });

    $(document).on('mouseenter', '[data-control="epigtor-link"]', function() {
        if (epigtorIsEditing) {
            $(this).epigtorLink();
        }
    });

    $(window).scroll(function() {
        $(document).find('[data-control="epigtor-link"]').each(function(){
            if ($(this).data('oc.epigtorLink') != undefined)
                $(this).data('oc.epigtorLink').hideControlPanel()
        })
    });

}(window.jQuery);

function epigtorLinkModalHtml(content, elementId,
        showDelete, labelDelete, labelSave, labelCancel, labelLinkText, labelLinkType, labelLinkUrl, labelLinkReference, labelLinkIsNewTab) {
    let deleteButton = `<button type="button" class="epigtor-link-delete btn btn-danger">${labelDelete}</button>`;

    return `
    <div class="epigtor-link-container epig-popup modal-content epigtor-link epigtor" id="epigtor-link-container-${elementId}">
        <div class="modal-body">
            <div class="layout-row">
                <div class="form-group">
                    <label for="${elementId}-text">${labelLinkText}</label>
                    <input type="text" class="form-control" name="text" value="${content.text}" id="${elementId}-text">
                </div>
                <div class="form-group">
                    <label for="${elementId}-type">${labelLinkType}</label>
                    <select name="type" class="form-control" id="${elementId}-type">
                        <option></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="${elementId}-external_url">${labelLinkUrl}</label>
                    <input type="text" class="form-control" name="external_url" value="${content.external_url}" id="${elementId}-external_url">
                </div>
                <div class="form-group">
                    <label for="${elementId}-reference">${labelLinkReference}</label>
                    <select name="reference" id="${elementId}-reference">
                        <option></option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="checkbox" name="is_new_tab" ${content.is_new_tab ? 'checked' : ''} id="${elementId}-is_new_tab">
                    <label for="${elementId}-is_new_tab">${labelLinkIsNewTab}</label>
                </div>
                <input type="hidden" name="type" value="${content.type}">
                <input type="hidden" name="reference" value="${content.reference}">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="epigtor-link-save btn btn-primary">${labelSave}</button>
            <button type="button" class="epigtor-link-cancel btn btn-secondary">${labelCancel}</button>
            ${showDelete ? deleteButton : ''}	
        </div>
    </div>
    `;
}

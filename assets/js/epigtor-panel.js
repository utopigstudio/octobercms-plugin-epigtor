/*
 * Epigtor plugin
 *
 */

var epigtorIsEditing = false;

+function ($) { "use strict";

    var $controlPanel = $('<div />').addClass('epigtor-panel')
    var $editButton = $('<button />').addClass('epigtor-panel-edit').text('Edit').appendTo($controlPanel)
    var $cancelButton = $('<button />').addClass('epigtor-panel-edit epigtor-panel-finish').text('Finish editing').hide().appendTo($controlPanel)

    $(document.body).append($controlPanel)

    $editButton.on('click', function(){
        epigtorIsEditing = true;
        $editButton.hide();
        $cancelButton.show();
    });

    $cancelButton.on('click', function(){
        epigtorIsEditing = false;
        $editButton.show();
        $cancelButton.hide();

        $(document).find('[data-control="epigtor"]').each(function(){
            let $epigtor = $(this).data('oc.epigtor');
            if ($epigtor != undefined) {
                let $epigtor = $(this).data('oc.epigtor');
                if ($epigtor.$controlPanel.hasClass('active')) {
                    $epigtor.clickCancel();
                }
                $epigtor.hideControlPanel()
            }
        });
    });

}(window.jQuery);

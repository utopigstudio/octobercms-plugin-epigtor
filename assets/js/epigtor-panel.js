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
        $('.epigtor-image-empty').show();
        $('.epigtor-link-empty').show();
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

        $(document).find('[data-control="epigtor-richeditor"]').each(function(){
            let $epigtor = $(this).data('oc.epigtorRicheditor');
            if ($epigtor != undefined) {
                let $epigtor = $(this).data('oc.epigtorRicheditor');
                if ($epigtor.$controlPanel.hasClass('active')) {
                    $epigtor.clickCancel();
                }
                $epigtor.hideControlPanel()
            }
        });
 
        $(document).find('[data-control="epigtor-image"]').each(function(){
            let $epigtor = $(this).data('oc.epigtorImage');
            if ($epigtor != undefined) {
                let $epigtor = $(this).data('oc.epigtorImage');
                if ($epigtor.$imageContainer.hasClass('visible')) {
                    $epigtor.clickCancel();
                }
                $epigtor.hideControlPanel()
            }
        });

        $(document).find('[data-control="epigtor-link"]').each(function(){
            let $epigtor = $(this).data('oc.epigtorLink');
            if ($epigtor != undefined) {
                let $epigtor = $(this).data('oc.epigtorLink');
                if ($epigtor.$linkContainer.hasClass('visible')) {
                    $epigtor.clickCancel();
                }
                $epigtor.hideControlPanel()
            }
        });

        $('.epigtor-image-empty').hide();
        $('.epigtor-link-empty').hide();
    });

    $(window).scroll(function() {
        hideControlPanels();
    });

}(window.jQuery);

function epigtorDragElement(elmnt) {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    if (document.getElementById(elmnt.id + "header")) {
        /* if present, the header is where you move the DIV from:*/
        document.getElementById(elmnt.id + "header").onmousedown = dragMouseDown;
    } else {
        /* otherwise, move the DIV from anywhere inside the DIV:*/
        elmnt.onmousedown = dragMouseDown;
    }

    function dragMouseDown(e) {
        e = e || window.event;
        // get the mouse cursor position at startup:
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        // call a function whenever the cursor moves:
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        // calculate the new cursor position:
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        // set the element's new position:
        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        /* stop moving when mouse button is released:*/
        document.onmouseup = null;
        document.onmousemove = null;
    }
}

function hideControlPanels() {
    $(document).find('[data-control="epigtor"]').each(function(){
        if ($(this).data('oc.epigtor') != undefined) {
            $(this).data('oc.epigtor').hideControlPanel()
        }
    });
    $(document).find('[data-control="epigtor-richeditor"]').each(function(){
        if ($(this).data('oc.epigtorRicheditor') != undefined)
            $(this).data('oc.epigtorRicheditor').hideControlPanel()
    });
    $(document).find('[data-control="epigtor-image"]').each(function(){
        if ($(this).data('oc.epigtorImage') != undefined) {
            $(this).data('oc.epigtorImage').hideControlPanel()
        }
    });
    $(document).find('[data-control="epigtor-link"]').each(function(){
        if ($(this).data('oc.epigtorLink') != undefined)
            $(this).data('oc.epigtorLink').hideControlPanel()
    });
}

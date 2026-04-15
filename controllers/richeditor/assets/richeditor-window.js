+function () { "use strict";

    var richEditorWidget = null;
    var fullscreenAttempts = 0;
    var maxFullscreenAttempts = 20;

    function isDocumentFullscreenActive() {
        return $(document.body).hasClass('component-backend-document-fullscreen')
            || $('.component-backend-document.full-screen').length > 0;
    }

    function tryEnableFullscreen() {
        if (isDocumentFullscreenActive()) {
            return true;
        }

        var $documentFullscreenButton = $('.backend-toolbar-button[data-cmd="document:toggleFullscreen"]:first');
        if ($documentFullscreenButton.length && !$documentFullscreenButton.hasClass('pressed')) {
            $documentFullscreenButton.trigger('click');
        }

        if (isDocumentFullscreenActive()) {
            return true;
        }

        var $box = $('.fr-box:first');
        if ($box.length && $box.hasClass('fr-fullscreen')) {
            return true;
        }

        var editor = richEditorWidget && richEditorWidget.editor ? richEditorWidget.editor : null;

        if (editor && editor.commands && typeof editor.commands.exec === 'function') {
            editor.commands.exec('fullscreen');
        }
        else if (editor && editor.fullscreen && typeof editor.fullscreen.toggle === 'function') {
            editor.fullscreen.toggle();
        }
        else {
            var $fullscreenButton = $('.fr-command[data-cmd="fullscreen"]:first');
            if ($fullscreenButton.length) {
                $fullscreenButton.trigger('click');
            }
        }

        if (isDocumentFullscreenActive()) {
            return true;
        }

        $box = $('.fr-box:first');
        return $box.length && $box.hasClass('fr-fullscreen');
    }

    function ensureFullscreen() {
        if (tryEnableFullscreen()) {
            return;
        }

        fullscreenAttempts += 1;
        if (fullscreenAttempts >= maxFullscreenAttempts) {
            return;
        }

        setTimeout(ensureFullscreen, 150);
    }

    window.EpigtorRicheditorWindow = {
        onCancel: function () {
            window.close();
        },

        onSaved: function (data) {
            var payload = {
                type: 'epigtor-richeditor-saved',
                instanceId: data.instanceId,
                content: data.content
            };

            if (window.opener && !window.opener.closed) {
                window.opener.postMessage(payload, window.location.origin);
                window.close();
                return;
            }

            // If the browser blocks close(), at least notify the user.
            alert('Saved. You can close this tab.');
        }
    };

    $(document).on('init.oc.richeditor', 'textarea', function (e, widget) {
        richEditorWidget = widget || null;
        ensureFullscreen();
    });

    $(function () {
        ensureFullscreen();

        $(document).on('click', '[data-action="cancel"]', function (e) {
            e.preventDefault();
            window.EpigtorRicheditorWindow.onCancel();
        });
    });

}();

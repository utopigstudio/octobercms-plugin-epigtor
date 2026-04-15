+function () { "use strict";

    window.EpigtorRicheditorWindow = {
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
}();

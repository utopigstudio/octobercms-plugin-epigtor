+function () { "use strict";

    window.EpigtorImageWindow = {
        onCanceled: function () {
            window.close();
        },

        onSaved: function (data) {
            if (!data || data.saved !== true) {
                return;
            }

            var payload = {
                type: 'epigtor-image-saved',
                instanceId: data.instanceId,
                imageId: data.imageId,
                hasImage: data.hasImage
            };

            if (window.opener && !window.opener.closed) {
                window.opener.postMessage(payload, window.location.origin);
                window.close();
                return;
            }

            alert('Saved. You can close this tab.');
        }
    };

}();

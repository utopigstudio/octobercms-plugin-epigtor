import octoberCommands from '../../../../../modules/backend/vuecomponents/richeditordocumentconnector/assets/js/octobercommands.js';

const script = document.querySelector('script[data-epigtor-image-extensions]');
const serverExtensions = JSON.parse(script.getAttribute('data-epigtor-image-extensions'));

// October's Vue upload command bypasses Froala's configured image type filter.
octoberCommands.uploadImage = function ($textarea) {
    const editor = $textarea.data('froala.editor');
    const allowedTypes = editor.opts.imageAllowedTypes.map(type => type.toLowerCase());
    const extensions = serverExtensions.filter(extension => allowedTypes.includes(extension.toLowerCase()));

    if (!extensions.length || editor.opts.imageUpload === false) {
        return;
    }

    this.uploadMedia(
        link => $textarea.froalaEditor('image.insert', link, true),
        $textarea,
        extensions.map(extension => '.' + extension).join(',')
    );
};

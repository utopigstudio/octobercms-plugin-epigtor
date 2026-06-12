;(function ($) { "use strict";
  $(document).render(function () {
    if (!$.FroalaEditor || !$.FroalaEditor.DEFAULTS) {
      return;
    }
    $.FroalaEditor.DEFAULTS = $.extend({}, $.FroalaEditor.DEFAULTS, {
      listAdvancedTypes: false,
      pastePlain: true,
      pasteDeniedTags: ['div', 'section', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7', 'img'],
      pasteDeniedAttrs: ['class', 'id', 'style'],
      pasteAllowedStyleProps: [],
      imageDefaultWidth: "100%",
      imageDefaultAlign: "center"
    });
  });
}(window.jQuery));
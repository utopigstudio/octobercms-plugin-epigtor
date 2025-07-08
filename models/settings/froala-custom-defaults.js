+function ($) { "use strict";
  $(document).render(function() {
    if ($.FroalaEditor) {
      $.FroalaEditor.DEFAULTS = $.extend($.FroalaEditor.DEFAULTS, {
        //disable advanced list types:
        listAdvancedTypes: false,

        //or add more options below...
      });
    }
  })
}(window.jQuery);
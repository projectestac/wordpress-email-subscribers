(function () {
  var undo_style_to_image = (function () {
      'use strict';

      var global = tinymce.util.Tools.resolve('tinymce.PluginManager');

        var Cell = function (initial) {
          var value = initial;
          var get = function () {
            return value;
          };
          var set = function (v) {
            value = v;
          };
          var clone = function () {
            return Cell(get());
          };
          return {
            get: get,
            set: set,
            clone: clone
          };
        };

        var handleSetContent = function (editor, headState, footState, event) {
          if ( event.content ) {
            event.content = event.content.replace( /<img([\w\W]+?)[\/]?>/g, function( match, tag ) {
             var imgElem  = jQuery.parseHTML(match);
             var imgClass = jQuery(imgElem).attr('class');
             var imgAlt   = jQuery(imgElem).attr('alt');
             if ( 'mce-object' === imgClass && '<style>' === imgAlt ) {
              var styleElem = jQuery(imgElem).attr('data-wp-preserve');
              var style = decodeURIComponent(styleElem);
              return style;
             } else {
               return match;
             }
            });
          }
        };

        // add plugin code here
        var setup = function (editor, headState, footState) {
          if ( 'edit-es-broadcast-body' !== editor.id ) {
            return;
          }
          
          editor.on('BeforeSetContent', function (event) {
            handleSetContent(editor, headState, footState, event);
          });
        };
        var FilterContent = { setup: setup };

        global.add('undo_style_to_image', function (editor) {
          var headState = Cell(''), footState = Cell('');
          FilterContent.setup(editor, headState, footState);
        });
  }());
})();
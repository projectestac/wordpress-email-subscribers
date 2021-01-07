/*
 *	JavaScript Wordpress editor
 *	Author: 		Shrimp2t
 *	Author URI: 	http://shrimp2t.com
 *	Version: 		1.0.0
 *	License:
 *		Copyright (c) 2013 Ante Primorac
 *		Permission is hereby granted, free of charge, to any person obtaining a copy
 *		of this software and associated documentation files (the "Software"), to deal
 *		in the Software without restriction, including without limitation the rights
 *		to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *		copies of the Software, and to permit persons to whom the Software is
 *		furnished to do so, subject to the following conditions:
 *
 *		The above copyright notice and this permission notice shall be included in
 *		all copies or substantial portions of the Software.
 *
 *		THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *		IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *		FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *		AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *		LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *		OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *		THE SOFTWARE.
 *	Usage:
 *		client side(jQuery):
 *			$( 'textarea').wp_js_editor();
 */


(function ( $ ) {

    window._wpEditor = {
        init: function( id , content, settings ){
            var _id = '__wp_mce_editor__';
            var _tpl =  $( '#_wp-mce-editor-tpl').html();
            if (  typeof content === "undefined"  ){
                content = '';
            }

            if (  typeof window.tinyMCEPreInit.mceInit[ _id ]  !== "undefined" ) {

                var tmceInit = _.clone( window.tinyMCEPreInit.mceInit[_id] );
                var qtInit = _.clone( window.tinyMCEPreInit.qtInit[_id] );

                tmceInit = $.extend( tmceInit , settings.tinymce );
                qtInit   = $.extend( qtInit , settings.qtag );

                var tpl = _tpl.replace( new RegExp(_id,"g"), id );
                
                // Email Subscriber custom code start.
                var textarea_name = $('#' + id ).attr('name');
                // Email Subscriber custom code end.
                
                var template =  $( tpl );
                $( "#"+id ).replaceWith( template );

                // Email Subscriber custom code start.
                if( 'undefined' !== typeof textarea_name ) {
                    $( "#"+id ).attr('name', textarea_name);
                }
                // Email Subscriber custom code end.

                // set content
                $( '#'+id ).val( content );

                $wrap = tinymce.$( '#wp-' + id + '-wrap' );

                tmceInit.body_class = tmceInit.body_class.replace(new RegExp(_id,"g"), id );
                tmceInit.selector   = tmceInit.selector.replace(new RegExp(_id,"g"), id );
                tmceInit.cache_suffix   = '';

                $wrap.removeClass( 'html-active').addClass( 'tmce-active' );

                tmceInit.init_instance_callback = function( editor ){
                    //switchEditors.go( new_id, 'tmce' );
                    if (  typeof settings === 'object' ) {
                        // editor.theme.resizeTo('100%', 500);
                        if( typeof settings.init_instance_callback === "function" ) {
                            settings.init_instance_callback( editor );
                        }

                        if (settings.sync_id !== '') {
                            if (typeof settings.sync_id === 'string') {
                                editor.on('keyup change', function (e) {
                                    $('#' + settings.sync_id).val(editor.getContent() ).trigger('change');
                                });
                            } else {
                                editor.on('keyup change', function (e) {
                                    settings.sync_id.val( editor.getContent() ).trigger('change');
                                });
                            }
                        }
                    }
                };

                tinyMCEPreInit.mceInit[ id ] = tmceInit;
                tmceInit['content'] = 'test content';

                qtInit.id = id;
                tinyMCEPreInit.qtInit[ id ] = qtInit;

                if ( $wrap.hasClass( 'tmce-active' ) || ! tinyMCEPreInit.qtInit.hasOwnProperty( id )  ) {
                    tinymce.init( tmceInit );
                    if ( ! window.wpActiveEditor ) {
                        window.wpActiveEditor = id;
                    }
                }

                if ( typeof quicktags !== 'undefined' ) {
                    quicktags( qtInit );
                    if ( ! window.wpActiveEditor ) {
                        window.wpActiveEditor = id;
                    }

                }

            }
        },

        sync: function(){
            //
        },

        remove: function( id ){
            var content = '';
            var editor = false;
            if ( editor = tinymce.get(id) ) {
                content = editor.getContent();
                editor.remove();
            } else {
                content = $( '#'+id ).val();
            }

            if ( $( '#wp-' + id + '-wrap').length > 0 ) {
                window._wpEditorBackUp = window._wpEditorBackUp || {};
                if (  typeof window._wpEditorBackUp[ id ] !== "undefined" ) {
                    $( '#wp-' + id + '-wrap').replaceWith( window._wpEditorBackUp[ id ] );
                }
            }

            $( '#'+id ).val( content );
        }

    };


    $.fn.wp_js_editor = function( options ) {

        // This is the easiest way to have default options.
        if ( options !== 'remove' ) {
            options = $.extend({
                sync_id: "", // sync to another text area
                tinymce: {}, // tinymce setting
                qtag:    {}, // quick tag settings
                init_instance_callback: function(){} // quick tag settings
            }, options );
        } else{
            options =  'remove';
        }

        return this.each( function( ) {
            var edit_area  =  $( this );
            edit_area.uniqueId();
            // Make sure edit area have a id attribute
            var id =  edit_area.attr( 'id' ) || '';
            if ( id === '' ){
                return ;
            }

            if ( 'remove' !== options ) {
                window._wpEditorBackUp = window._wpEditorBackUp || {};
                window._wpEditorBackUp[ id ] =  edit_area;
                window._wpEditor.init( id, edit_area.val(), options );
            } else {
                window._wpEditor.remove( id );
            }

        });

    };

}( jQuery ));


/* global CoursePress */

(function() {
    'use strict';

    CoursePress.Define( 'UnitCollection', function( $, doc, win) {
        return Backbone.Collection.extend({
            url: win._coursepress.ajaxurl + '?action=coursepress_get_course_units&_wpnonce=' + win._coursepress._wpnonce,
            unitsLoaded: false,
            initialize: function( courseId ) {
                this.url += '&course_id=' + courseId;
                this.on( 'error', this.serverError, this );

                this.fetch();
            },
            parse: function( response ) {
                this.unitsLoaded = true;
                this.trigger( 'coursepress:unit_collection_loaded', response.data );
                return response.data;
            },
            serverError: function() {
                // @todo: show server error
                window.alert('error');
            }
        });

    });
})();
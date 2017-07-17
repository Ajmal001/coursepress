/* global CoursePress */

(function() {
    'use strict';

    CoursePress.Define( 'Step_DISCUSSION', function() {
        return CoursePress.View.extend({
            template_id: 'coursepress-step-discussion',
            events: {
                'change [name="meta_show_content"]': 'toggleContent'
            },
            initialize: function( model ) {
                model = _.extend({
                    meta_mandatory: false,
                    meta_show_content: false,
                    post_content: ''
                }, model );
                this.model = new CoursePress.Request(model);
                this.on( 'view_rendered', this.setUI, this );
                this.render();
            },
            setUI: function() {
                var self = this;

                this.description = this.$('.cp-step-description');
                this.visualEditor({
                    container: this.description,
                    content: this.model.get( 'post_content' ),
                    callback: function( content ) {
                        self.model.set( 'post_content', content );
                    }
                });
            },
            toggleContent: function(ev) {
                var sender = this.$(ev.currentTarget),
                    is_checked = sender.is(':checked'),
                    content = this.$('.cp-step-description');

                if ( is_checked ) {
                    content.slideDown();
                } else {
                    content.slideUp();
                }
            }
        });
    });
})();
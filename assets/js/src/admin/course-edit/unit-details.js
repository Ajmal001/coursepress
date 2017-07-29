/* global CoursePress */

(function() {
    'use strict';

    CoursePress.Define( 'UnitDetails', function($, doc, win) {
        return CoursePress.View.extend({
            template_id: 'coursepress-unit-details',
            controller: false,
            with_modules: false,
            events: {
                'change [name="meta_use_feature_image"]': 'toggleFeatureImage',
                'change [name="meta_use_description"]': 'toggleDescription',
                'change [name="meta_unit_availability"]': 'toggleAvailability',
                'keyup .unit-title-input': 'updateUnitTitle',
                'change [name]': 'updateModel',
                'focus [name]': 'removeErrorMarker'
            },

            initialize: function( model, controller ) {
                this.controller = controller;
                this.editCourseView = controller.editCourseView;
                this.with_modules = this.editCourseView.model.get('meta_with_modules');
                this.model.set( 'with_modules', this.with_modules );

                this.on( 'view_rendered', this.setUpUI, this );
                this.render();
            },

            initialize333: function( model, controller ) {
                this.controller = controller;
                this.editCourseView = controller.editCourseView;
                this.with_modules = this.editCourseView.model.get('meta_with_modules');
                this.model.set( 'with_modules', this.with_modules );

                this.controller.on( 'coursepress:validate-unit', this.validateUnit, this );
                this.on( 'coursepress:model_updated', this.updateUnitCollection );
                this.on( 'view_rendered', this.setUpUI, this );
                this.render();
            },

            validateUnit: function(unitView) {
                var proceed, title, use_feature_img, modules,
                    errors = {}, error_count, popup, steps, steps_count;

                if ( ! unitView.proceed ) {
                    return;
                }

                proceed = true;
                title = this.model.get('post_title');
                use_feature_img = this.model.get('meta_use_feature_image');

                if ( ! title || 'Untitled' === title ) {
                    this.$('[name="post_title"]').parent().addClass('cp-error');
                    proceed = false;
                }

                if ( use_feature_img && ! this.model.get('meta_unit_feature_image') ) {
                    this.$('[name="meta_unit_feature_image"]').parent().addClass('cp-error');
                    proceed = false;
                }

                if ( this.with_modules ) {
                    modules = this.model.get('modules');

                    if ( modules ) {
                        _.each( modules, function( module, index ) {
                            if ( module && index > 0 ) {
                                if ( ! module.title || 'Untitled' === module.title ) {
                                    errors.noname_module = win._coursepress.text.noname_module;
                                }

                                steps_count = _.keys( module.steps );

                                if ( steps_count <= 0 ) {
                                    errors.no_steps = win._coursepress.text.nosteps;
                                }
                            }
                        }, this );
                    }
                } else {
                    steps = this.model.get('steps');
                    steps_count = _.keys(steps);
                    if ( steps_count <= 0 ) {
                        errors.no_steps = win._coursepress.text.nosteps;
                    }
                }
                error_count = _.keys(errors);

                if ( error_count.length > 0 ) {
                    proceed = false;
                    errors = _.values(errors);
                    popup = new CoursePress.PopUp({
                        type: 'warning',
                        message: errors.join('')
                    });
                }

                unitView.proceed = proceed;
            },

            setUpUI: function() {
                var self;

                self = this;
                this.feature_image = new CoursePress.AddImage( this.$('#unit-feature-image') );
                this.$('select').select2();

                this.visualEditor({
                    content: this.model.get('post_content'),
                    container: this.$('.cp-unit-description'),
                    callback: function(content) {
                        self.model.set( 'post_content', content );
                    }
                });

                this.container = this.$('#unit-steps-container');

                if ( this.with_modules ) {
                    this.modules = new CoursePress.UnitModules(this.model, this);
                    this.modules.$el.appendTo(this.container);
                } else {
                    //this.steps = new CoursePress.Unit_Steps( this.model, this );
                    //this.steps.$el.appendTo(this.container);
                }
            },

            toggleFeatureImage: function(ev) {
                var sender = this.$(ev.currentTarget),
                    is_checked = sender.is(':checked'),
                    feature = this.$('.cp-unit-feature-image');

                feature[ is_checked ? 'slideDown' : 'slideUp']();
            },

            toggleDescription: function( ev ) {
                var sender = this.$(ev.currentTarget),
                    is_checked = sender.is(':checked'),
                    desc = this.$('.cp-unit-description');

                desc[ is_checked ? 'slideDown' : 'slideUp']();
            },

            toggleAvailability: function( ev ) {
                var sender = this.$(ev.currentTarget),
                    value = sender.val(),
                    divs = this.$('.cp-on_date, .cp-after_delay');

                divs.slideUp();

                if ( 'instant' !== value ) {
                    this.$('.cp-' + value).slideDown();
                }
            },

            updateUnitTitle: function( ev ) {
                var sender = this.$(ev.currentTarget),
                    value = sender.val();

                CoursePress.Events.trigger( 'coursepress:change_unit_title', value, this.model.cid );
            },

            updateUnitCollection: function() {
            }
        });
    });
})();
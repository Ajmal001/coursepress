/* global CoursePress */

(function() {
    'use strict';

    CoursePress.Define( 'NotificationAlerts', function( $, doc, win ) {
        return CoursePress.View.extend({
            template_id: 'coursepress-notification-alerts-tpl',
            el: $('#notification-alerts'),
            events: {
                    'change .cp-toggle-alert-status': 'toggleAlertStatus',
                    'click .row-actions .cp-delete': 'deleteNotification',
                    'click .row-actions .cp-restore': 'restoreNotification',
                    'click .row-actions .cp-trash': 'trashNotification',
            },

            initialize: function() {
                    this.request = new CoursePress.Request();

                    // On status toggle fail.
                    this.request.on( 'coursepress:error_alert_status_toggle', this.revertStatusToggle, this );
                    // On trash, delete, restore or duplicate notification.
                    this.request.on( 'coursepress:success_change_post', this.reloadNotifications, this );

                    this.render();
            },

            /**
             * Toggle course status.
             */
            toggleAlertStatus: function( ev ) {
                    this.request.selector = $(ev.target);
                    var status = this.request.selector.prop('checked') ? 'publish' : 'draft';
                    this.request.set( {
                            'action' : 'alert_status_toggle',
                            'alert_id' : this.request.selector.val(),
                            'status' : status
                    } );
                    this.request.save();
            },

            /**
             * Revert toggled status.
             */
            revertStatusToggle: function() {
                    var checked = this.request.selector.prop('checked');
                    this.request.selector.prop('checked', !checked);
            },

            /**
             * Trash Notification
             */
            trashNotification: function(ev) {
                this.id = this.$(ev.currentTarget).closest('td').data('id');
                if ( this.id ) {
                    this.request.set({
                        action: 'change_post',
                        id: this.id,
                        type: 'notification',
                        cp_action: 'trash',
                    });
                    this.request.save();
                }
                return false;
            },

            restoreNotification: function(ev) {
                this.id = this.$(ev.currentTarget).closest('td').data('id');
                if ( this.id ) {
                    this.request.set({
                        action: 'change_post',
                        id: this.id,
                        type: 'notification',
                        cp_action: 'restore',
                    });
                    this.request.save();
                }
                return false;
            },

            deleteNotification: function(ev) {
                var confirm, sender, dropdown;
                sender = this.$(ev.currentTarget);
                this.id = sender.closest('td').data('id');
                dropdown = sender.parents('.cp-dropdown');
                confirm = new CoursePress.PopUp({
                    type: 'warning',
                    message: win._coursepress.text.delete_post
                });
                confirm.on( 'coursepress:popup_ok', this.deleteCurrentNotification, this );
                dropdown.removeClass('open');
                return false;
            },

            deleteCurrentNotification: function() {
                if ( this.id ) {
                    new CoursePress.PopUp({
                        type: 'info',
                        message: win._coursepress.text.deleting_post
                    });
                    this.request.set({
                        action: 'change_post',
                        id: this.id,
                        type: 'notification',
                        cp_action: 'delete',
                    });
                    this.request.save();
                }
            },

            /**
             * Reload the current page.
             */
            reloadNotifications: function() {
                location.reload();
            },
        });
    });

})();
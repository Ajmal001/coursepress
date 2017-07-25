<script type="text/template" id="coursepress-notification-alerts-tpl">

    <a href="javascript:void(0);" class="cp-btn cp-btn-active cp-notification-menu-item notifications-alerts_form" data-page="alerts_form" data-tab="alerts"><?php _e( 'New Course Alert', 'cp' ); ?></a>

    <table class="coursepress-table" cellspacing="0">
        <thead>
        <tr>
            <?php foreach ( $columns as $column_id => $column_label ) : ?>
                <th class="manage-column column-<?php echo $column_id; echo in_array( $column_id, $hidden_columns ) ? ' hidden': ''; ?>" id="<?php echo $column_id; ?>">
                    <?php echo $column_label; ?>
                </th>
            <?php endforeach; ?>
            <th class="column-status"><?php _e( 'Status', 'cp' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php $odd = true; ?>
        <?php if ( ! empty( $notifications ) ) : ?>
            <?php foreach ( $notifications as $notification ) : ?>
                <tr class="<?php echo $odd ? 'odd' : 'even'; ?>">

                    <?php foreach ( array_keys( $columns ) as $column_id ) : ?>
                        <td class="column-<?php echo $column_id; echo in_array( $column_id, $hidden_columns ) ? ' hidden': ''; ?>">
                            <?php
                            switch( $column_id ) :
                                case 'title' :
                                    echo $notification->post_title;
                                    break;
                                case 'course' :
                                    // Get course name.
                                    $course_id = get_post_meta( $notification->ID, 'alert_course', true );
                                    echo empty( $course_id ) ? __( 'All Courses', 'cp' ) : get_the_title( $course_id );
                                    break;
                                default :
                                    /**
                                     * Trigger to allow custom column value
                                     *
                                     * @since 3.0
                                     * @param string $column_id
                                     * @param object $notification
                                     */
                                    do_action( 'coursepress_notifications_list_column', $column_id, $notification );
                                    break;
                            endswitch;
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="column-status">
                        <label>
                            <?php $active =  ( isset( $notification->post_status ) && $notification->post_status === 'publish' ); ?>
                            <input type="checkbox" class="cp-toggle-input cp-toggle-alert-status" value="<?php echo $notification->ID; ?>" <?php checked( $active, true ); ?> /> <span class="cp-toggle-btn"></span>
                        </label>
                    </td>
                </tr>
                <?php $odd = $odd ? false : true; ?>
            <?php endforeach; ?>
        <?php else : ?>
            <tr class="odd">
                <td><?php _e( 'No notifications found.', 'cp' ); ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if ( ! empty( $list_table ) ) : ?>
        <div class="tablenav cp-admin-pagination">
            <?php $list_table->pagination( 'bottom' ); ?>
        </div>
    <?php endif; ?>

</script>
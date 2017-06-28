<?php
/**
 * Class CoursePress_Certificate
 *
 * @since 2.0
 */
class CoursePress_Certificate extends CoursePress_Utility {
    const CUSTOM_FIELD_NAME_FOR_PDF_FILE = 'certificate_file';

    protected $post_type = 'cp_certificate';
    static $certificate_id = 0;

    public function __construct() {}

    function is_enabled() {
        return coursepress_get_setting( 'basic_certificate/enabled', true );
    }

    /**
     * Inserts a new certificate into the DB and returns the created post_id.
     *
     * Note that we need to save this twice:
     * First time the post_content is empty/dummy, then on the second pass we
     * populate the content, as we need to know the post_id to generate it.
     *
     * @since  2.0.0
     * @param int $student_id
     * @param int $course_id
     * @return int Post-ID
     */
    protected function create_certificate( $student_id, $course_id ) {
        $post = array(
            'post_author' => $student_id,
            'post_parent' => $course_id,
            'post_status' => 'private', // Post is only visible for post_author.
            'post_type' => $this->post_type,
            'post_content' => 'Processing...', // Intentional value.
            'post_title' => 'Basic Certificate',
            'ping_status' => 'closed',
            'meta_input' => array(
                self::CUSTOM_FIELD_NAME_FOR_PDF_FILE => $this->get_pdf_file_name( $course_id, $student_id ),
            ),
        );

        // Stage 1: Save data to get post_id!
        $certificate_id = wp_insert_post( $post );

        $post['ID'] = $certificate_id;
        $post['post_content'] = $this->get_certificate_content( $certificate_id );

        // Stage 2: Save final certificate data!
        wp_update_post(
            apply_filters( 'coursepress_pre_insert_post', $post )
        );

        /**
         * generate pdf file here!
         */
        $this->generate_pdf_certificate( $course_id, $student_id, false, $post );

        return $certificate_id;
    }

    /**
     * get certificate id
     *
     * @since  2.0.0
     *
     * @param  int $student_id The WP user-ID.
     * @param  int $course_id The course-ID that was completed.
     * @return integer/boolean Returns certificate id or false.
     */
    function get_certificate_id( $student_id, $course_id ) {
        // First check, if the student is already certified for the course.
        $params = array(
            'author' => $student_id,
            'post_parent' => $course_id,
            'post_type' => $this->post_type,
            'post_status' => 'any',
        );
        $res = get_posts( $params );
        if ( is_array( $res ) && count( $res ) ) {
            return $res[0]->ID;
        }

        return false;
    }

    /**
     * Generate the certificate, store it in DB and send email to the student.
     *
     * @since  2.0.0
     * @param  int $student_id The WP user-ID.
     * @param  int $course_id The course-ID that was completed.
     *
     * @return int|null
     */
    function generate_certificate( $student_id, $course_id ) {
        if ( ! $this->is_enabled() ) { return false; }

        $certificate_id = $this->get_certificate_id( $student_id, $course_id );
        if ( empty( $certificate_id ) ) {
            $certificate_id = $this->create_certificate( $student_id, $course_id );
        }
        // And finally: Send that email :)
        $this->send_certificate( $certificate_id );

        return true;
    }

    /**
     * Send certificate to student.
     *
     * @since 2.0.0
     * @param  int $student_id The WP user-ID.
     * @param  int $course_id The course-ID that was completed.
     * @return bool True on success.
     */
    function send_certificate( $certificate_id ) {
        if ( ! $this->is_enabled() ) { return false; }

        //$email_args = $this->fetch_params( $certificate_id );

        // Hooked to `wp_mail' filter to attached PDF Certificate as attachment.
        self::$certificate_id = $certificate_id;
        add_filter( 'wp_mail', array( __CLASS__, 'attached_pdf_certificate' ) );

        /**
         * @todo: Send email
         *
        return CoursePress_Helper_Email::send_email(
            CoursePress_Helper_Email::BASIC_CERTIFICATE,
            $email_args
        );
         **/

        return true;
    }

    /**
     * Returns an array with all certificate details needed fo send the email
     * and to process the certificate contents.
     *
     * @since  2.0.0
     * @param  int $certificate_id The post-ID of the certificate.
     * @return bool|array Array with certificate details.
     */
    protected function fetch_params( $certificate_id ) {
        if ( ! $this->is_enabled() ) { return array(); }

        $student_id = (int) get_post_field( 'post_author', $certificate_id );
        $course_id = (int) get_post_field( 'post_parent', $certificate_id );
        $completion_date = get_post_field( 'post_date', $certificate_id );

        if ( ! empty( $completion_date ) ) {
            $date_format = get_option( 'date_format' );
            $completion_date = date_i18n( $date_format, strtotime( $completion_date ) );
        }

        if ( empty( $student_id ) || empty( $course_id ) ) {
            return false;
        }

        $student = get_userdata( $student_id );
        if ( empty( $student ) ) {
            return false;
        }

        $course = get_post( $course_id );

        $course_name = $course->post_title;
        $valid_stati = array( 'draft', 'pending', 'auto-draft' );

        if ( in_array( $course->post_status, $valid_stati ) ) {
            $course_address =  coursepress_get_course_permalink( $course_id );
        } else {
            $course_address = get_permalink( $course_id );
        }

        $params = array();
        $params['student_id'] = $student_id;
        $params['course_id'] = $course_id;
        $params['email'] = sanitize_email( $student->user_email );
        $params['first_name'] = $student->first_name;
        $params['last_name'] = $student->last_name;
        $params['completion_date'] = $completion_date;
        $params['certificate_id'] = $certificate_id;
        $params['course_name'] = $course_name;
        $params['course_address'] = $course_address;
        $params['unit_list'] = '';//CoursePress_Data_Course::get_units_html_list( $course_id );

        return $params;
    }

    public function default_certificate_content() {
        $msg = __(
            '<h2>%1$s %2$s</h2>
			has successfully completed the course

			<h3>%3$s</h3>

			<h4>Date: %4$s</h4>
			<small>Certificate no.: %5$s</small>', 'cp'
        );

        $default_certification_content = sprintf(
            $msg,
            'FIRST_NAME',
            'LAST_NAME',
            'COURSE_NAME',
            'COMPLETION_DATE',
            'CERTIFICATE_NUMBER',
            'UNIT_LIST'
        );

        return $default_certification_content;
    }

    /**
     * Parse the Certificate template and return HTML code to render the
     * certificate.
     *
     * @since  2.0.0
     * @param  int $certificate_id The post-ID of the certificate.
     * @return string HTML code to display the certificate.
     */
    protected function get_certificate_content( $certificate_id ) {
        $data = $this->fetch_params( $certificate_id );

        $content = coursepress_get_setting( 'basic_certificate/content' );

        // Check if custom certificate is enable
        $course_id = $data['course_id'];
        $course = coursepress_get_course( $course_id );

        $is_override = $course->__get( 'basic_certificate' );

        if ( ! empty( $is_override ) ) {
            $content = $course->__get( 'basic_certificate_layout' );
        } else {
            $use_cp_default = coursepress_get_setting( 'basic_certificate/use_cp_default', false );

            if ( ! empty( $use_cp_default ) ) {
                $content = $this->default_certificate_content();
            }
        }

        $vars = array(
            'FIRST_NAME' => sanitize_text_field( $data['first_name'] ),
            'LAST_NAME' => sanitize_text_field( $data['last_name'] ),
            'COURSE_NAME' => sanitize_text_field( $data['course_name'] ),
            'COMPLETION_DATE' => sanitize_text_field( $data['completion_date'] ),
            'CERTIFICATE_NUMBER' => (int) $data['certificate_id'],
            'UNIT_LIST' => $data['unit_list'],
        );

        /**
         * Filter variables before applying changes.
         *
         * @param (array) $vars.
         **/
        $vars = apply_filters( 'coursepress_basic_certificate_vars', $vars );

        return $this->replace_vars( $content, $vars );
    }

    /**
     * get pdf file name.
     *
     * @since 2.0.0
     *
     * @param integer $course_id Course ID.
     * @param integer $student_id student ID.
     * @return mixed
     */
    function get_pdf_file_name( $course_id, $student_id, $basedir = 'include' )
    {
        global $CoursePress;

        $pdf = $CoursePress->get_class( 'CoursePress_PDF' );
        $filename = (defined('NONCE_KEY') && NONCE_KEY) ? NONCE_KEY : rand();
        $filename .= $course_id . $student_id;
        $filename = md5($filename);
        $filename .= '.pdf';
        /**
         * subdirectory to avoid mass file storage in one directory
         */
        $dir = substr($filename, 0, 2);
        $subdirectory = sprintf('%s/', $dir);
        $pdf->check_dir($subdirectory);
        $dir = substr($filename, 2, 2);
        $subdirectory .= sprintf('%s/', $dir);
        $pdf->check_dir($subdirectory);
        $filename = substr($filename, 4);
        /**
         * add basedir or not?
         */
        if ('no-base-dir' == $basedir) {
            $pdf_file = $subdirectory . $filename;
        } else {
            $pdf_file = $pdf->cache_path($subdirectory) . $filename;
        }

        return $pdf_file;
    }

    /**
     * @param $course_id
     * @param string $student_id
     * @param bool $download
     * @return array|bool|string
     */
    function generate_pdf_certificate( $course_id, $student_id = '', $download = false ) {
        global $CoursePress;

        if ( empty( $student_id ) ) {
            $student_id = get_current_user_id();
        }
        $post_params = array(
            'post_type' => $this->post_type,
            'author' => $student_id,
            'post_parent' => $course_id,
            'post_status' => 'any',
        );

        $post = get_posts( $post_params );
        $course = coursepress_get_course( $course_id );
        $is_override = $course->__get( 'basic_certificate' );
        $is_override = ! empty( $is_override );

        if ( count( $post ) > 0 || $is_override ) {
            $pdf = $CoursePress->get_class( 'CoursePress_PDF' );

            $post = $post[0];
            // We'll replace the existing content to a new one to apply settings changes when applicable.
            $certificate = $this->get_certificate_content( $post->ID );
            $settings = coursepress_get_setting( 'basic_certificate' );
            $background = coursepress_get_array_val( $settings, 'background_image', '' );
            $orientation = coursepress_get_array_val( $settings, 'orientation', 'L' );
            $margins = (array) coursepress_get_array_val( $settings, 'margin' );

            $filename = $this->get_pdf_file_name( $course_id, $student_id, 'no-base-dir' );
            $logo = array();
            $text_color = coursepress_get_setting( 'basic_certificate/text_color', array() );
            $text_color = coursepress_convert_hex_color_to_rgb( $text_color, array() );
            /**
             * Is certificate overrided?
             */
            if ( $is_override ) {
                $margins = $course->__get( 'cert_margin', array() );
                $orientation = $course->__get( 'page_orientation' );
                $background = $course->__get( 'certificate_background' );
                $text_color = $course->__get( 'cert_text_color' );
                $text_color = coursepress_convert_hex_color_to_rgb( $text_color, array() );

            } else {
                /**
                 * Use CP defaults?
                 */
                $use_cp_default = coursepress_get_setting( 'basic_certificate/use_cp_default', false );

                if ( ! empty( $use_cp_default ) ) {
                    /**
                     * Default Background
                     */
                    $background = $CoursePress->plugin_path .'/asset/img/certificate/certificate-background-p.png';
                    /**
                     * default orientation
                     */
                    $orientation = 'P';
                    /**
                     * CP Logo
                     */
                    $logo = array(
                        'file' => $CoursePress->plugin_path . '/asset/img/certificate/certificate-logo-coursepress.png',
                        'x' => 95,
                        'y' => 15,
                        'w' => 100,
                    );
                    /**
                     * Default margins
                     */
                    $margins = array(
                        'left' => 40,
                        'right' => 40,
                        'top' => 100,
                    );
                    /**
                     * default color
                     */
                    $text_color = array( 90, 90, 90 );
                }
            }


            // Set the content
            $certificate = stripslashes( $certificate );
            $html = coursepress_create_html( 'div', array( 'class' => 'basic_certificate' ), $certificate );

            /**
             * Allow others to modify the HTML layout.
             *
             * @since 2.0
             *
             * @param (string) $html			Current HTML layout.
             * @param (int) $course_id			The course ID the certificate is generated from.
             * @param (int) $student_id			The student ID the certificate is generated to.
             **/
            $html = apply_filters( 'coursepress_basic_certificate_html', $html, $course_id, $student_id );
            $certificate_title = apply_filters( 'coursepress_certificate_title', __( 'Certificate of Completion', 'CP_TD' ) );
            $args = array(
                'title' => $certificate_title,
                'orientation' => $orientation,
                'image' => $background,
                'filename' => $filename,
                'format' => 'F',
                'uid' => $post->ID,
                'page_break' => 'no',
                'margins' => apply_filters( 'coursepress_basic_certificate_margins', $margins ),
                'logo' => apply_filters( 'coursepress_basic_certificate_logo', $logo ),
                'text_color' => apply_filters( 'coursepress_basic_certificate_text_color', $text_color ),
            );
            if ( $download ) {
                $args['format'] = 'FI';
                $args['force_download'] = true;
                $args['url'] = true;
            }
            return $pdf->make_pdf( $html, $args );
        }
        return false;
    }

    public function pdf_notice() {
        global $CoursePress;

        $pdf = $CoursePress->get_class( 'CoursePress_PDF' );

        $cache_path = $pdf->cache_path();

        $message = coursepress_create_html(
            'p',
            array(),
            sprintf( __( 'CoursePress cannot generate PDF because directory is not writable: %s', 'CP_TD' ), $cache_path )
        );

        echo coursepress_create_html( 'div', array( 'class' => 'notice notice-error' ), $message );
    }

    /**
     * check and create subdirectory.
     *
     * @since 2.0.4
     *
     * @param string $subdirectory subdirectory
     */
    public static function check_dir( $subdirectory ) {
        $uploads_dir = wp_upload_dir();
        $cache_path = apply_filters( 'coursepress_pdf_cache_path', trailingslashit( $uploads_dir['basedir'] ) . 'pdf-cache/' );
        $check_directory = $cache_path . $subdirectory;
        if ( ! is_dir( $check_directory) ) {
            mkdir($check_directory);
        }
    }
}
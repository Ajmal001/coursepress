<?php
/**
 * @copyright Incsub ( http://incsub.com/ )
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 ( GPL-2.0 )
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 */

// @codingStandardsIgnoreFile This file uses functions from external libraries
//                            that do not adhere to WP Coding Standards.

/**
 * Class to Extend TCPF.
 *
 * @since 1.2.1
 *
 * @return object
 */
class CoursePress_Helper_PDF extends TCPDF {

	private $footer_text = '';

	// short side then long side
	protected $dimensions = array(
		'A0' => array( 841, 1189 ),
		'A1' => array( 594, 841 ),
		'A2' => array( 420, 594 ),
		'A3' => array( 297, 420 ),
		'A4' => array( 210, 297 ),
		'A5' => array( 148, 210 ),
		'A6' => array( 105, 148 ),
		'A7' => array( 74, 105 ),
		'A8' => array( 52, 74 ),
	);

	public function Footer() {
		$the_font = apply_filters( 'coursepress_pdf_font', 'helvetica' );

		// Position at 15 mm from bottom
		$this->SetY( -15 );
		// Set font
		$this->SetFont( $the_font, '', 10 );
		// Page number
		$this->Cell( 0, 5, $this->footer_text, 0, false, 'L', 0, '', 0, false, 'T', 'M' );
		$this->Cell( 0, 5, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'T' );
		$line_width = (0.85 / $this->k);

		// $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color));
		$pageWidth = $this->getPageWidth();   // Get total page width, without margins
		$pageMargins = $this->getMargins();     // Get all margins as array
		$headerMargin = $pageMargins['footer']; // Get the header margin
		$px2 = $pageWidth - $headerMargin; // Compute x value for second point of line
		$p1x = $headerMargin;

		$px2 = $pageWidth - $pageMargins['right'];
		$p1x = $pageMargins['left'];

		// $p1x = $this->getX();
		$p1y = $this->getY();
		$p2x = $px2;
		$p2y = $p1y;  // Use same y for a straight line
		$style = array( 'width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $this->footer_line_color );
		$style = array();
		$this->Line( $p1x, $p1y, $p2x, $p2y, $style );

	}

	public static function fonts() {

		// Saving system resources, we wont scan the font directory.
		$fonts = array(
			'cid0cs.php' => __( 'Arial Unicode MS (Simplified Chinese)', 'CP_TD' ),
			'cid0ct.php' => __( 'Arial Unicode MS (Chinese Traditional)', 'CP_TD' ),
			'cid0jp.php' => __( 'Arial Unicode MS (Japanese)', 'CP_TD' ),
			'cid0kr.php' => __( 'Arial Unicode MS (Korean)', 'CP_TD' ),
			'courier.php' => __( 'Courier', 'CP_TD' ),
			'courierb.php' => '',
			'courierbi.php' => '',
			'courieri.php' => '',
			'helvetica.php' => __( 'Helvetica', 'CP_TD' ),
			'helveticab.php' => '',
			'helveticabi.php' => '',
			'helveticai.php' => '',
			'symbol.php' => __( 'Symbol', 'CP_TD' ),
			'times.php' => __( 'Times-Roman', 'CP_TD' ),
			'timesb.php' => '',
			'timesbi.php' => '',
			'timesi.php' => '',
			'uni2cid_ac15.php' => __( 'Adobe-CNS1-5', 'CP_TD' ),
			'uni2cid_ag15.php' => __( 'Adobe-GB1-5', 'CP_TD' ),
			'uni2cid_aj16.php' => __( 'Adobe-Japan1-6', 'CP_TD' ),
			'uni2cid_ak12.php' => __( 'Adobe-Korea1-2', 'CP_TD' ),
			'zapfdingbats.php' => __( 'ZapfDingbats', 'CP_TD' ),
		);

		if ( defined( 'TCPDF_PLUGIN_ACTIVE' ) && TCPDF_PLUGIN_ACTIVE ) {
			$fonts = array_merge( $fonts, array(
				'aealarabiya.php' => __( 'Al Arabiya', 'CP_TD' ),
				'aefurat.php' => __( 'Furat', 'CP_TD' ),
				'dejavusans.php' => __( 'DejaVu Sans', 'CP_TD' ),
				'dejavusansb.php' => '',
				'dejavusansbi.php' => '',
				'dejavusanscondensed.php' => __( 'DejaVu Sans Condensed', 'CP_TD' ),
				'dejavusanscondensedb.php' => '',
				'dejavusanscondensedbi.php' => '',
				'dejavusanscondensedi.php' => '',
				'dejavusansextralight.php' => __( 'DejaVu Sans ExtraLight', 'CP_TD' ),
				'dejavusansi.php' => '',
				'dejavusansmono.php' => __( 'DejaVu Sans Mono', 'CP_TD' ),
				'dejavusansmonob.php' => '',
				'dejavusansmonobi.php' => '',
				'dejavusansmonoi.php' => '',
				'dejavuserif.php' => __( 'DejaVu Serif', 'CP_TD' ),
				'dejavuserifb.php' => '',
				'dejavuserifbi.php' => '',
				'dejavuserifcondensed.php' => __( 'DejaVu Serif Condensed', 'CP_TD' ),
				'dejavuserifcondensedb.php' => '',
				'dejavuserifcondensedbi.php' => '',
				'dejavuserifcondensedi.php' => '',
				'dejavuserifi.php' => '',
				'freemono.php' => __( 'Free Mono', 'CP_TD' ),
				'freemonob.php' => '',
				'freemonobi.php' => '',
				'freemonoi.php' => '',
				'freesans.php' => __( 'Free Sans', 'CP_TD' ),
				'freesansb.php' => '',
				'freesansbi.php' => '',
				'freesansi.php' => '',
				'freeserif.php' => __( 'Free Serif', 'CP_TD' ),
				'freeserifb.php' => '',
				'freeserifbi.php' => '',
				'freeserifi.php' => '',
				'hysmyeongjostdmedium.php' => __( 'MyungJo Medium (Korean)', 'CP_TD' ),
				'kozgopromedium.php' => __( 'Kozuka Gothic Pro (Japanese Sans-Serif)', 'CP_TD' ),
				'kozminproregular.php' => __( 'Kozuka Mincho Pro (Japanese Serif)', 'CP_TD' ),
				'msungstdlight.php' => __( 'MSung Light (Traditional Chinese)', 'CP_TD' ),
				'pdfacourier.php' => __( 'PDFA Courier', 'CP_TD' ),
				'pdfacourierb.php' => '',
				'pdfacourierbi.php' => '',
				'pdfacourieri.php' => '',
				'pdfahelvetica.php' => __( 'PDFA Helvetica', 'CP_TD' ),
				'pdfahelveticab.php' => '',
				'pdfahelveticabi.php' => '',
				'pdfahelveticai.php' => '',
				'pdfasymbol.php' => __( 'PDFA Symbol', 'CP_TD' ),
				'pdfatimes.php' => __( 'PDFA Times', 'CP_TD' ),
				'pdfatimesb.php' => '',
				'pdfatimesbi.php' => '',
				'pdfatimesi.php' => '',
				'pdfazapfdingbats.php' => __( 'PDFA ZapfDingbats', 'CP_TD' ),
				'stsongstdlight.php' => __( 'STSong Light (Simplified Chinese)', 'CP_TD' ),
			) );
		}

		// If you are hooking this, make sure you are using fonts for TCPDF and that they are located in relevant font path
		return apply_filters( 'coursepress_pdf_font_list', $fonts );

	}

	protected function getFontsList() {

		$fonts = CoursePress_Helper_PDF::fonts();
		$font_path = apply_filters( 'coursepress_pdf_font_path', TCPDF_FONTS::_getfontpath() );

		foreach ( $fonts as $font => $font_name ) {
			array_push( $this->fontlist, strtolower( trailingslashit( $font_path ) . $font ) );
		}

	}

	public static function get_format_in_mm( $format ) {
		$dimension = TCPDF_STATIC::getPageSizeFromFormat( $format );
		$dimension[0] = round( $dimension[0] / 72 * 25.5 );
		$dimension[1] = round( $dimension[1] / 72 * 25.5 );

		return $dimension;
	}

	public static function get_format_in_px( $format, $dpi = 300 ) {
		$ppm = $dpi * 0.03937008;
		$dimensions = self::get_format_in_mm( $format );
		$dimensions[0] = $dimensions[0] / $ppm;
		$dimensions[1] = $dimensions[1] / $ppm;

		return $dimensions;
	}

	/**
	 * Make the actual PDF
	 *
	 * @param $html
	 * @param array $args
	 *
	 * 'title' - PDF Title
	 * 'orientation' - [P]ortrait, [L]andscape, [A]utomatic
	 * 'base_font' - See TCPDF
	 * 'clickable' - True creates clickable links
	 * 'style' - Additional Style tag
	 * 'image' - Background image
	 * 'uid' - Defined unique ID (generated if left empty)
	 * 'filename' - Override auto filename
	 * 'format' - See TCPDF (F - save file, I - standard output (preview), D - download )
	 * 'header' - Header content - array( 'Header Title', 'Subtitle' )
	 * 'footer' - Footer content
	 *
	 * @return array|string
	 */
	public static function make_pdf( $html, $args = array() ) {

		if ( ! isset( $args['title'] ) || empty( $args['title'] ) ) {
			$args['title'] = __( 'CoursePress Report', 'CP_TD' );
		}

		$the_font = apply_filters( 'coursepress_pdf_font', 'helvetica' );

		// If filtering, please make sure both path and url refer to the same location
		$cache_path = self::cache_path(); //apply_filters( 'coursepress_pdf_cache_path', trailingslashit( CoursePress::$path ) . 'pdf-cache/' );
		$furl_path = self::cache_url(); //apply_filters( 'coursepress_pdf_cache_url', trailingslashit( CoursePress::$url ) . 'pdf-cache/' );

		$page_orientation = isset( $args['orientation'] ) ? $args['orientation'] : PDF_PAGE_ORIENTATION;

		// Make directory for receipt cache
		if ( ! is_dir( $cache_path ) ) {
			mkdir( $cache_path, 0755, true );
			touch( trailingslashit( $cache_path ) . 'index.php' );
		}
		if ( ! is_writable( $cache_path ) ) {
			chmod( $cache_path, 0755 );
		}

		// Clean out old cache files
		foreach ( glob( $cache_path . '*.pdf' ) as $fname ) {
			$age = time() - filemtime( $fname );
			if ( ( $age > 12 * 60 * 60 ) && ( basename( $fname ) != 'index.php' ) ) { // Don't erase our blocking index.php file
				unlink( $fname ); // more than 12 hours old;
			}
		}

		// create new PDF document
		$pdf = new CoursePress_Helper_PDF( $page_orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

		// $dimension = $this->get_format_in_mm( PDF_PAGE_FORMAT );
		$dimension = self::get_format_in_px( PDF_PAGE_FORMAT );
		if ( 'P' == $page_orientation ) {
			$temp = $dimension[0];
			$dimension[0] = $dimension[1];
			$dimension[1] = $temp;
		}

		// Note: If uncommenting below, please remove previous call.
		// Can use the following to change language symbols to appropriate standard, e.g. ISO-638-2 languages.
		// $pdf = new TCPDF( $page_orientation, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-639-2', false );
		// set document information
		$pdf->SetCreator( CoursePress::$name );
		$pdf->SetTitle( $args['title'] );
		$pdf->SetKeywords( '' );

		if ( isset( $args['header'] ) && is_array( $args['header'] ) ) {

			$title = isset( $args['header']['title'] ) ? $args['header']['title'] : '';
			$subtitle = isset( $args['header']['subtitle'] ) ? $args['header']['subtitle'] : '';

			if ( empty( $subtitle ) ) {
				$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
			}

			// setHeaderData($ln='', $lw=0, $ht='', $hs='', $tc=array(0,0,0), $lc=array(0,0,0)
			$pdf->SetHeaderData( '', '', $title, $subtitle );
			$pdf->setHeaderFont( array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );

		} else {
			// remove default header
			$pdf->setPrintHeader( false );
			// adjust margin
			$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
		}

		if ( isset( $args['footer'] ) ) {

			$pdf->footer_text = $args['footer'];
			$pdf->setFooterFont( array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );
		} else {
			// remove default footer
			$pdf->setPrintFooter( false );
			// adjust margins
			$pdf->SetFooterMargin( PDF_MARGIN_FOOTER );
		}

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

		// set image scale factor
		$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

		// set some language-dependent strings
		global $l;
		$pdf->setLanguageArray( $l );

		// ---------------------------------------------------------
		// set font
		if ( isset( $args['base_font'] ) ) {
			$pdf->SetFont( $args['base_font']['family'], '', $args['base_font']['size'] );
		} else {
			$pdf->SetFont( $the_font, '', 14 );
		}

		// add a page
		$pdf->AddPage();

		if ( isset( $args['clickable'] ) && ! empty( $args['clickable'] ) ) {
			$html = wpautop( $html );
		} else {
			$html = make_clickable( wpautop( $html ) );
		}

		if ( isset( $args['style'] ) ) {
			$html = $args['style'] . $html;
		}

		if ( isset( $args['image'] ) && ! empty( $args['image'] ) ) {
			$pdf->SetMargins( 0, 0, 0 );
			$pdf->SetAutoPageBreak( false, 0 );
			$pdf->Image( $args['image'], 0, 0, 0, 0, '', '', '', true, 300, '', false, false, 0, false, false, true );
			$pdf->setPageMark();
		}

		// $pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
		// set auto page breaks
		$pdf->SetAutoPageBreak( true, PDF_MARGIN_BOTTOM );

		// output the HTML content
		$pdf->writeHTML( $html, true, false, true, false, '' );

		// ---------------------------------------------------------
		global $blog_id;
		$user_id = get_current_user_id();

		$sitename = sanitize_title( get_site_option( 'blogname' ) );

		$uid = isset( $args['uid'] ) ? "{$sitename}-{$blog_id}-{$user_id}" . $args['uid'] : uniqid( "{$sitename}-{$blog_id}-{$user_id}" );

		$file = isset( $args['filename'] ) ? $args['filename'] : $uid . '.pdf';

		$fname = $cache_path . $file;

		$furl = $furl_path . $file;

		if ( ! isset( $args['format'] ) || empty( $args['format'] ) ) {
			$args['format'] = 'F';
		}

		switch ( $args['format'] ) {
			case 'F':
				// Close and output PDF document
				$pdf->Output( $fname, 'F' );
				if ( isset( $args['url'] ) && ! empty( $args['url'] ) ) {
					if ( isset( $args['force_download'] ) && ! empty( $args['force_download'] ) ) {
						CoursePress_Helper_Utility::download_file_request( $furl );
					} else {
						return $furl;
					}
				} else {
					$attachments[] = $fname;
				}

				return $attachments;
				break;
			case 'I' :
			case 'D' :
				$pdf->Output( $fname, $args['format'] );
				exit;
				break;
		}

    }

	public static function cache_path() {
		$uploads_dir = wp_upload_dir();
		$cache_path = apply_filters( 'coursepress_pdf_cache_path', trailingslashit( $uploads_dir['basedir'] ) . 'pdf-cache/' );

		return $cache_path;
	}

	public static function cache_url() {
		$uploads_dir = wp_upload_dir();
		$cache_url = apply_filters( 'coursepress_pdf_cache_url', trailingslashit( $uploads_dir['baseurl'] ) . 'pdf-cache/' );

		return $cache_url;
	}

    /**
     * Check pdf-cache directory.
     *
     * @since 2.0.0
     *
     * @return boolen is writable or not?
     */
    public static function is_cache_path_writable( ) {

        $cache_path = self::cache_path();
        $is_writable = is_dir( $cache_path ) && is_writable( $cache_path );
        if ( ! $is_writable ) {
            error_log(
                sprintf(
                    __( 'CoursePress cannot generate PDF because directory is not writable: %s', 'CP_TD' ),
                    $cache_path
                )
            );
        }
        return $is_writable;

    }
}

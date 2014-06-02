<?php
/**
 * TablePress Footable.
 *
 * @package   TablePress_FooTable
 * @author    Julien Liabeuf <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://n2clic.com
 * @copyright 2014 ThemeAvenue
 *
 * @wordpress-plugin
 * Plugin Name:       TablePress Extension:  FooTable
 * Plugin URI:        http://themeavenue.net
 * Description:       Adds support for FooTable to TablePress.
 * Version:           0.1.0
 * Author:            ThemeAvenue
 * Author URI:        http://themeavenue.net
 * Text Domain:       tpfoo
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

/* Define shortcuts */
define( 'TPFOO_URL', plugin_dir_url( __FILE__ ) );
define( 'TPFOO_PATH', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', array( 'TablePress_FooTable', 'get_instance' ) );
/**
 * Main class
 */
class TablePress_FooTable {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '0.1.0';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {

		/* If TablePress is not active, let's not do anything */
		if( false === $this->check_tablepress() )
			return false;

		/* Store the triggers */
		$this->triggers = array();

		/* Set breakpoints */
		$this->break_phone  = apply_filters( 'tpfoo_breakpoint_phone', 480 );
		$this->break_tablet = apply_filters( 'tpfoo_breakpoint_tablet', 992 );

		/* Load resources */
		add_action( 'wp_print_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_styles', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_footer', array( $this, 'footable_loader' ), 20 );

		/* Hook into TablePress */
		add_filter( 'tablepress_cell_tag_attributes', array( $this, 'update_cell_attributes' ), 10, 7 );
		add_filter( 'tablepress_cell_content', array( $this, 'clean_cell_content' ), 20, 4 );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Check if TablePress is installed and active.
	 *
	 * @since  1.0.0
	 * @return bool True if the plugin is enabled
	 */
	public function check_tablepress() {

		if( class_exists( 'TablePress' ) && in_array( 'tablepress/tablepress.php', (array) get_option( 'active_plugins', array() ) ) )
			return true;

		else
			return false;

	}

	/**
	 * Enqueue plugin scripts
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'tpfoo-footable', TPFOO_URL . 'assets/vendor/FooTable-2/dist/footable.min.js', array( 'jquery' ), self::VERSION, true );
	}

	/**
	 * Enqueue plugin styles
	 *
	 * @since  1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'tpfoo-footable', TPFOO_URL . 'assets/vendor/FooTable-2/css/footable.core.min.css', array(), self::VERSION, 'all' );
	}

	/**
	 * Initialize FooTable.
	 *
	 * The script is too small to be loaded as a .js
	 * depandancy, the extra HTTP request is not worthy.
	 * We initialize it directly in the source.
	 *
	 * @since  1.0.0
	 */
	public function footable_loader() { ?>

		<script type='text/javascript'>
			(function ($) {
				'use strict';

				$(function () {

					// check if jQuery FooTable is loaded
					if (jQuery().footable) {

						var bpPhone = <?php echo $this->break_phone; ?>,
							bpTable = <?php echo $this->break_tablet; ?>;

						if ($.mobile !== undefined) {
							// initialiaze normally
							$('.tablepress').footable({
								breakpoints: {
									phone: bpPhone,
									tablet: bpTable
								}
							});
						} else {
							// if you use jQuery Mobile, this is how to initialize FooTable the right way
							$(document).on('pageshow', function () {
								$('.tablepress').footable({
									breakpoints: {
										phone: bpPhone,
										tablet: bpTable
									}
								});
							});
						}

					}

				});

			}(jQuery));
		</script>

	<?php }

	/**
	 * Get trigger keywords.
	 *
	 * @since  1.0.0
	 * @return array A list of keywords that can trigger a new attribute
	 */
	public function get_trigger_keywords() {

		$keywords = array(
			'#hide-phone#',
			'#hide-tablet#',
			'#toggle#',
		);

		return apply_filters( 'tpfoo_trigger_keywords', $keywords );

	}

	/**
	 * Parse the cell content.
	 *
	 * Parse the content and search for one of the allowed trigger keywords.
	 *
	 * @since  1.0.0
	 * @param  string $content Content to parse
	 * @return array           Elements matching the pattern in the given string
	 */
	public function parse_content( $content ) {

		/* Set to false by default */
		$triggers = false;

		/* Search everything contained inbetween ## */
		preg_match_all( "/#(.*?)#/", $content, $matches );

		/* If we have a match, prepare the array */
		if( isset( $matches[0] ) && !empty( $matches[0] ) ) {

			$triggers = array();

			foreach( $matches[0] as $key => $value ) {
				$triggers[$value] = $matches[1][$key];
			}

		}

		return $triggers;

	}

	/**
	 * Remove trigger keywords from cell content.
	 *
	 * As the filter tablepress_cell_content is executed before tablepress_cell_tag_attributes,
	 * the cells content will be empty while trying to add the attributes. Hence, we store the
	 * potential triggers in a local var that we'll check later.
	 *
	 * @since  1.0.0
	 * @param  string  $cell_content Current cell content
	 * @param  integer $table_id     Current table ID
	 * @param  integer $row_idx      Current row index
	 * @param  integer $col_idx      Current column index
	 * @return string                Cell content without the trigger keyword(s)
	 */
	public function clean_cell_content( $cell_content, $table_id, $row_idx, $col_idx ) {

		if( 1 == $row_idx ) {

			$triggers = $this->parse_content( $cell_content );
			$keywords = $this->get_trigger_keywords();

			if( false !== $triggers ) {

				foreach( $triggers as $trigger => $value ) {

					if( in_array( $trigger, $keywords ) )
						$cell_content = str_replace( $trigger, '', $cell_content );
				}

				$this->triggers[$table_id][$col_idx] = $triggers;

			}

		}

		return trim( $cell_content );

	}

	/**
	 * Add required data attributes to cell.
	 *
	 * @since  1.0.0
	 * @param  array   $tag_attributes List of current cell attributes
	 * @param  integer $table_id       Current table ID
	 * @param  integer $cell_content   Current cell content
	 * @param  integer $row_idx        Current row index
	 * @param  integer $col_idx        Current column index
	 * @param  integer $colspan_idx    The number of combined columns for this cell
	 * @param  integer $rowspan_idx    The number of combined rows for this cell
	 * @return array                   List of cell attributes with possible new FooTable attributes
	 */
	public function update_cell_attributes( $tag_attributes, $table_id, $cell_content, $row_idx, $col_idx, $colspan_idx, $rowspan_idx ) {

		if( 1 == $row_idx ) {

			// $triggers = $this->parse_content( $cell_content ); var_dump( $triggers );
			$triggers = isset( $this->triggers[$table_id][$col_idx] ) ? $this->triggers[$table_id][$col_idx] : false;
			$keywords = $this->get_trigger_keywords();

			if( false !== $triggers ) {

				foreach( $triggers as $trigger => $value ) {

					if( !in_array( $trigger, $keywords ) )
						continue;

					if( 'toggle' == $value ) {

						$tag_attributes['data-toggle'] = 'true';

					} else {

						$trigger = explode( '-', $value );

						if( 'hide' == $trigger[0] ) {

							if( !isset( $hide ) )
								$hide = array();

							array_push( $hide, $trigger[1] );

						}
					}

				}

				if( !empty( $hide ) ) {

					$hide = implode( ',', $hide );
					$tag_attributes['data-hide'] = $hide;

				}

			}

		}

		return $tag_attributes;

	}

}
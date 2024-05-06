<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wmue_Main_Controller extends Ai1wmve_Main_Controller {

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Sub-level Settings menu
		add_submenu_page(
			'ai1wm_export',
			__( 'Settings', AI1WMUE_PLUGIN_NAME ),
			__( 'Settings', AI1WMUE_PLUGIN_NAME ),
			'export',
			'ai1wmue_settings',
			'Ai1wmue_Settings_Controller::index'
		);
	}

	/**
	 * Register listeners for actions
	 *
	 * @return void
	 */
	protected function activate_actions() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_export_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backups_scripts_and_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_scripts_and_styles' ), 20 );
	}

	/**
	 * Export and import commands
	 *
	 * @return void
	 */
	public function ai1wm_commands() {
		if ( ai1wmue_is_running() ) {
			add_filter( 'ai1wm_export', 'Ai1wmue_Export_Retention::execute', 270 );
			add_filter( 'ai1wm_import', 'Ai1wmue_Import_Settings::execute', 290 );
			add_filter( 'ai1wm_import', 'Ai1wmue_Import_Database::execute', 310 );
		}
	}

	/**
	 * Check whether All-in-One WP Migration is loaded
	 *
	 * @return void
	 */
	public function ai1wm_loaded() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

		// Settings
		add_action( 'admin_post_ai1wmue_settings', 'Ai1wmue_Settings_Controller::settings' );
	}

	/**
	 * WP CLI commands
	 *
	 * @return void
	 */
	public function ai1wmve_wp_cli() {
		if ( defined( 'WP_CLI' ) ) {
			WP_CLI::add_command(
				'ai1wm',
				'Ai1wm_Backup_WP_CLI_Command',
				array(
					'shortdesc'     => __( 'All-in-One WP Migration Command', AI1WMUE_PLUGIN_NAME ),
					'before_invoke' => array( $this, 'activate_extension_commands' ),
				)
			);
		}
	}

	/**
	 * Activates extension specific commands
	 *
	 * @return void
	 */
	public function activate_extension_commands() {
		$_GET['file'] = 1;
		$this->ai1wm_commands();
	}

	/**
	 * Display All-in-One WP Migration notice
	 *
	 * @return void
	 */
	public function ai1wm_notice() {
		?>
		<div class="error">
			<p>
				<?php
				_e(
					'Unlimited Extension requires <a href="https://wordpress.org/plugins/all-in-one-wp-migration/" target="_blank">All-in-One WP Migration plugin</a> to be activated. ' .
					'<a href="https://help.servmask.com/knowledgebase/install-instructions-for-unlimited-extension/" target="_blank">Unlimited Extension install instructions</a>',
					AI1WMUE_PLUGIN_NAME
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts and styles for Import Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_import_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_import', $hook ) === false ) {
			return;
		}

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Enqueue scripts and styles for Backup Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_backups_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_backups', $hook ) === false ) {
			return;
		}

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Enqueue scripts and styles for Export Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_export_scripts_and_styles( $hook ) {
		if ( stripos( 'toplevel_page_ai1wm_export', $hook ) === false ) {
			return;
		}

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Enqueue scripts and styles for Settings Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_settings_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wmue_settings', $hook ) === false ) {
			return;
		}

		if ( is_rtl() ) {
			wp_enqueue_style(
				'ai1wmue_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.rtl.css', 'AI1WMUE' ),
				array( 'ai1wm_servmask' )
			);
		} else {
			wp_enqueue_style(
				'ai1wmue_settings',
				Ai1wm_Template::asset_link( 'css/settings.min.css', 'AI1WMUE' ),
				array( 'ai1wm_servmask' )
			);
		}

		wp_enqueue_script(
			'ai1wmue_settings',
			Ai1wm_Template::asset_link( 'javascript/settings.min.js', 'AI1WMUE' ),
			array( 'ai1wm_settings' )
		);

		wp_localize_script(
			'ai1wmue_settings',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wmue_settings',
			'ai1wmue_folder_browser',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmue_folder_browser' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wmue_settings',
			'ai1wmue_locale',
			array(
				'folder_browser_change' => __( 'Change', AI1WMUE_PLUGIN_NAME ),
				'title_name'            => __( 'Name', AI1WMUE_PLUGIN_NAME ),
				'title_date'            => __( 'Date', AI1WMUE_PLUGIN_NAME ),
				'empty_list_message'    => __( 'No folders to list. Click on the navbar to go back.', AI1WMUE_PLUGIN_NAME ),
				'legend_select_info'    => __( 'Select with a click', AI1WMUE_PLUGIN_NAME ),
				'legend_open_info'      => __( 'Open with two clicks', AI1WMUE_PLUGIN_NAME ),
				'button_close'          => __( 'Close', AI1WMUE_PLUGIN_NAME ),
				'button_select'         => __( 'Select folder &gt;', AI1WMUE_PLUGIN_NAME ),
				'show_more'             => __( 'more', AI1WMUE_PLUGIN_NAME ),
				'show_less'             => __( 'less', AI1WMUE_PLUGIN_NAME ),
			)
		);

		add_action( 'admin_print_scripts', array( $this, 'google_tag_manager' ) );
	}

	/**
	 * Register initial router
	 *
	 * @return void
	 */
	public function router() {
		if ( current_user_can( 'export' ) ) {
			add_action( 'wp_ajax_ai1wmue_folder_browser', 'Ai1wmue_Settings_Controller::list_folders' );
		}
	}

	/**
	 * Add links to plugin list page
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === AI1WMUE_PLUGIN_BASENAME ) {
			$links[] = __( '<a href="https://help.servmask.com/knowledgebase/unlimited-extension-user-guide/" target="_blank">User Guide</a>', AI1WMUE_PLUGIN_NAME );
			$links[] = __( '<a href="https://servmask.com/contact-support" target="_blank">Contact Support</a>', AI1WMUE_PLUGIN_NAME );
		}

		return $links;
	}

	public function google_tag_manager() {
		Ai1wm_Template::render(
			'common/google-tag-manager',
			array(),
			AI1WMUE_TEMPLATES_PATH
		);
	}
}

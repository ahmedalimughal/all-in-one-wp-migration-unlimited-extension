<?php
/**
 * Copyright (C) 2014-2023 ServMask Inc.
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

if ( ! class_exists( 'Ai1wmve_Main_Controller' ) ) {

	abstract class Ai1wmve_Main_Controller {

		/**
		 * @var string
		 * Extension's prefix - must be all capital letters
		 * Examples: AI1WMUE', 'AI1WMXE', 'AI1WMGE' ...
		 */
		protected $plugin_prefix;

		/**
		 * @var string
		 * Name of $params key that triggers export/import actions
		 * Examples: 'file', 'direct_push', 'gdrive' ...
		 */
		protected $plugin_params_key;

		/**
		 * @var string
		 */
		protected $plugin_base_name;

		/**
		 * @var string
		 */
		protected $plugin_name;

		protected $purchase_id;

		/**
		 * @var string
		 */
		protected $min_ai1wm_version;

		/**
		 * Main Application Controller
		 *
		 * @param string $plugin_prefix     Plugin's prefix - must be all capital letters (AI1WMUE', 'AI1WMGE' ...)
		 * @param string $plugin_params_key Name of $params key that triggers export/import actions ('file', 'gdrive' ...)
		 *
		 * @throws Ai1wmve_Error_Exception
		 */
		public final function __construct( $plugin_prefix, $plugin_params_key ) {
			$this->plugin_prefix     = $plugin_prefix;
			$this->plugin_params_key = $plugin_params_key;

			$this->ai1wve_check_if_constants_are_defined();

			register_activation_hook( $this->plugin_base_name, array( $this, 'ai1wmve_activation_hook' ) );

			// Activate hooks
			$this->ai1wmve_activate_actions();
			$this->ai1wmve_activate_filters();
		}

		/**
		 * Check if we have all constants defined
		 *
		 * @return void
		 * @throws Ai1wmve_Error_Exception
		 */
		private function ai1wve_check_if_constants_are_defined() {
			$message = __( '%s constant must be defined!', $this->plugin_name );

			if ( ! defined( $constant = $this->plugin_prefix . '_PLUGIN_BASENAME' ) ) {
				throw new Ai1wmve_Error_Exception( sprintf( $message, $constant ) );
			}
			$this->plugin_base_name = constant( $constant );

			if ( ! defined( $constant = $this->plugin_prefix . '_PLUGIN_NAME' ) ) {
				throw new Ai1wmve_Error_Exception( sprintf( $message, $constant ) );
			}
			$this->plugin_name = constant( $constant );

			if ( ! defined( $constant = $this->plugin_prefix . '_PURCHASE_ID' ) ) {
				throw new Ai1wmve_Error_Exception( sprintf( $message, $constant ) );
			}
			$this->purchase_id = constant( $constant );

			if ( defined( $constant = $this->plugin_prefix . '_MIN_AI1WM_VERSION' ) ) {
				$this->min_ai1wm_version = constant( $constant );
			}
		}

		/**
		 * Add links to plugin list page
		 *
		 * @return array
		 */
		abstract public function plugin_row_meta( $links, $file );

		/**
		 * Export and import commands
		 *
		 * @return void
		 */
		abstract public function ai1wm_commands();

		/**
		 * Register initial router
		 *
		 * @return void
		 */
		abstract public function router();

		/**
		 * Display All-in-One WP Migration notice
		 *
		 * @return void
		 */
		abstract public function ai1wm_notice();

		/**
		 * Check whether All-in-One WP Migration has been loaded
		 *
		 * @return void
		 */
		abstract protected function ai1wm_loaded();

		/**
		 * Register listeners for actions (plugin specific)
		 * @see ai1wmve_activate_actions
		 *
		 * @return void
		 */
		abstract protected function activate_actions();

		/**
		 * Activation hook callback
		 *
		 * @return void
		 */
		public function ai1wmve_activation_hook() {
			// Create activation request
			if ( $this->purchase_id && defined( 'AI1WMVE_PURCHASE_ACTIVATION_URL' ) ) {
				global $wpdb;

				wp_remote_post(
					AI1WMVE_PURCHASE_ACTIVATION_URL,
					array(
						'timeout' => 15,
						'body'    => array(
							'url'           => get_site_url(),
							'email'         => get_option( 'admin_email' ),
							'wp_version'    => get_bloginfo( 'version' ),
							'php_version'   => PHP_VERSION,
							'mysql_version' => $wpdb->db_version(),
							'uuid'          => $this->purchase_id,
						),
					)
				);
			}
		}

		/**
		 * Initializes language domain for the plugin
		 *
		 * @return void
		 */
		public function ai1wmve_load_textdomain() {
			load_plugin_textdomain( $this->plugin_name, false, false );
		}

		/**
		 * Check whether All-in-One WP Migration is loaded
		 *
		 * @return void
		 */
		public function ai1wmve_loaded() {
			if ( ! defined( 'AI1WM_PLUGIN_NAME' ) ) {
				if ( is_multisite() ) {
					add_action( 'network_admin_notices', array( $this, 'ai1wm_notice' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'ai1wm_notice' ) );
				}
			} else {
				if ( is_multisite() ) {
					add_action( 'network_admin_menu', array( $this, 'ai1wmve_admin_menu' ), 20 );
				} else {
					add_action( 'admin_menu', array( $this, 'ai1wmve_admin_menu' ), 20 );
				}

				// Add export inactive themes
				if ( ! has_action( 'ai1wm_export_inactive_themes' ) ) {
					add_action( 'ai1wm_export_inactive_themes', 'Ai1wmve_Export_Controller::inactive_themes' );
				}

				// Add export inactive plugins
				if ( ! has_action( 'ai1wm_export_inactive_plugins' ) ) {
					add_action( 'ai1wm_export_inactive_plugins', 'Ai1wmve_Export_Controller::inactive_plugins' );
				}

				// Add export cache files
				if ( ! has_action( 'ai1wm_export_cache_files' ) ) {
					add_action( 'ai1wm_export_cache_files', 'Ai1wmve_Export_Controller::cache_files' );
				}

				// Add export exclude files
				if ( ! has_action( 'ai1wm_export_advanced_settings' ) ) {
					add_action( 'ai1wm_export_advanced_settings', 'Ai1wmve_Export_Controller::exclude_files' );
				}

				// Add export exclude db tables
				if ( ! has_action( 'ai1wm_export_exclude_db_tables' ) ) {
					add_action( 'ai1wm_export_exclude_db_tables', 'Ai1wmve_Export_Controller::exclude_db_tables' );
				}

				// Schedule event save
				if ( ! has_action( 'admin_post_ai1wm_schedule_event_save' ) ) {
					add_action( 'admin_post_ai1wm_schedule_event_save', 'Ai1wmve_Schedules_Controller::save' );
				}
				// Schedule event cron run action
				if ( ! has_action( Ai1wmve_Schedule_Event::CRON_HOOK ) ) {
					add_action( Ai1wmve_Schedule_Event::CRON_HOOK, 'Ai1wmve_Schedules_Controller::run' );
				}
				// Schedule event log actions
				add_action( 'ai1wm_status_export_done', 'Ai1wmve_Schedules_Controller::log_success' );
				add_action( 'ai1wm_status_export_fail', 'Ai1wmve_Schedules_Controller::log_failed' );

				// Register stats collect actions if URL is defined
				if ( defined( 'AI1WMVE_STATS_URL' ) ) {
					add_action( 'ai1wm_status_export_done', array( $this, 'ai1wmve_export_stats' ) );
					add_action( 'ai1wm_status_import_done', array( $this, 'ai1wmve_import_stats' ) );
				}

				// Register creating backup notes for reset hub backups
				add_action( 'ai1wm_status_export_done', array( $this, 'create_reset_backup_label' ) );

				// Add import unlimited
				add_filter( 'ai1wm_max_file_size', array( $this, 'ai1wmve_max_file_size' ) );

				$this->ai1wm_loaded();
			}
		}

		/**
		 * WP CLI commands
		 *
		 * @return void
		 */
		public function ai1wmve_wp_cli() {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::add_command(
					'ai1wm',
					'Ai1wm_Backup_WP_CLI_Command',
					array( 'shortdesc' => __( 'All-in-One WP Migration Command', $this->plugin_name ) )
				);
			}
		}

		/**
		 * WP CLI commands: extension
		 *
		 * @return void
		 */
		public function wp_cli_extension() {
		}

		/**
		 * Enqueue scripts and styles for Advanced Export Controller
		 *
		 * @param  string $hook Hook suffix
		 * @return void
		 */
		public function ai1wmve_enqueue_export_scripts_and_styles( $hook ) {
			if ( stripos( 'toplevel_page_ai1wm_export', $hook ) === false ) {
				return;
			}

			if ( is_rtl() ) {
				wp_enqueue_style(
					'ai1wmve_advanced_export',
					Ai1wm_Template::asset_link( 'css/pro-export.min.rtl.css', $this->plugin_prefix )
				);
			} else {
				wp_enqueue_style(
					'ai1wmve_advanced_export',
					Ai1wm_Template::asset_link( 'css/pro-export.min.css', $this->plugin_prefix )
				);
			}

			wp_enqueue_script(
				'ai1wmve_advanced_export',
				Ai1wm_Template::asset_link( 'javascript/pro-export.min.js', $this->plugin_prefix ),
				array( 'jquery' )
			);

			wp_localize_script(
				'ai1wmve_advanced_export',
				'ai1wmve_locale',
				array(
					'button_done'                        => __( 'Done', $this->plugin_name ),
					'loading_placeholder'                => __( 'Listing files ...', $this->plugin_name ),
					'selected_no_files'                  => __( 'No files selected', $this->plugin_name ),
					'selected_multiple'                  => __( '{x} files and {y} folders selected', $this->plugin_name ),
					'selected_multiple_folders'          => __( '{y} folders selected', $this->plugin_name ),
					'selected_multiple_files'            => __( '{x} files selected', $this->plugin_name ),
					'selected_one_file'                  => __( '{x} file selected', $this->plugin_name ),
					'selected_one_file_multiple_folders' => __( '{x} file and {y} folders selected', $this->plugin_name ),
					'selected_one_file_one_folder'       => __( '{x} file and {y} folder selected', $this->plugin_name ),
					'selected_one_folder'                => __( '{y} folder selected', $this->plugin_name ),
					'selected_multiple_files_one_folder' => __( '{x} files and {y} folder selected', $this->plugin_name ),
					'column_name'                        => __( 'Name', $this->plugin_name ),
					'column_date'                        => __( 'Date', $this->plugin_name ),
					'legend_select'                      => __( 'Click checkbox to toggle selection', $this->plugin_name ),
					'legend_expand'                      => __( 'Click folder name to expand', $this->plugin_name ),
					'error_message'                      => __( 'Something went wrong, please refresh and try again', $this->plugin_name ),
					'button_clear'                       => __( 'Clear selection', $this->plugin_name ),
					'empty_list_message'                 => __( 'Folder empty. Click on folder icon to close it.', $this->plugin_name ),
					'column_table_name'                  => __( 'Table Name', $this->plugin_name ),
					'selected_no_tables'                 => __( 'No tables selected', $this->plugin_name ),
					'selected_one_table'                 => __( '{x} table selected', $this->plugin_name ),
					'selected_multiple_tables'           => __( '{x} tables selected', $this->plugin_name ),
					'database_name'                      => DB_NAME,
				)
			);

			wp_localize_script(
				'ai1wmve_advanced_export',
				'ai1wmve_file_exclude',
				array(
					'ajax' => array(
						'url'        => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmve_file_list' ) ),
						'list_nonce' => wp_create_nonce( 'ai1wmve_list' ),
					),
				)
			);
		}

		/**
		 * Enqueue scripts and styles for Import Controller
		 *
		 * @param  string $hook Hook suffix
		 * @return void
		 */
		public function ai1wmve_enqueue_import_scripts_and_styles( $hook ) {
			if ( stripos( 'all-in-one-wp-migration_page_ai1wm_import', $hook ) === false ) {
				return;
			}

			wp_enqueue_script(
				'ai1wmve_uploader',
				Ai1wm_Template::asset_link( 'javascript/pro-uploader.min.js', $this->plugin_prefix ),
				array( 'jquery' )
			);

			wp_localize_script(
				'ai1wmve_uploader',
				'ai1wmve_uploader',
				array(
					'chunk_size'  => apply_filters( 'ai1wm_max_chunk_size', AI1WM_MAX_CHUNK_SIZE ),
					'max_retries' => apply_filters( 'ai1wm_max_chunk_retries', AI1WM_MAX_CHUNK_RETRIES ),
					'url'         => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_import' ) ),
					'params'      => array(
						'priority'   => 5,
						'secret_key' => get_option( AI1WM_SECRET_KEY ),
					),
					'filters'     => array(
						'ai1wm_archive_extension' => array( 'wpress' ),
						'ai1wm_archive_size'      => apply_filters( 'ai1wm_max_file_size', $this->ai1wmve_max_file_size() ),
					),
				)
			);
		}

		/**
		 * Enqueue scripts and styles for Backup Controller
		 *
		 * @param  string $hook Hook suffix
		 * @return void
		 */
		public function ai1wmve_enqueue_backups_scripts_and_styles( $hook ) {
			if ( stripos( 'all-in-one-wp-migration_page_ai1wm_backups', $hook ) === false ) {
				return;
			}

			wp_enqueue_script(
				'ai1wmue_restore',
				Ai1wm_Template::asset_link( 'javascript/pro-restore.min.js', $this->plugin_prefix ),
				array( 'jquery' )
			);
		}

		/**
		 * Enqueue scripts and styles for Schedules Controller
		 *
		 * @param  string $hook Hook suffix
		 * @return void
		 */
		public function ai1wmve_enqueue_schedules_scripts_and_styles( $hook ) {
			if ( stripos( 'all-in-one-wp-migration_page_ai1wmve_schedules', $hook ) === false ) {
				return;
			}

			// We don't want heartbeat to occur when restoring
			wp_deregister_script( 'heartbeat' );

			// We don't want auth check for monitoring whether the user is still logged in
			remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

			$action_url = isset( $_GET['action'] ) ? $_GET['action'] : null;
			switch ( $action_url ) {
				case 'create-event':
				case 'edit-event':
					$action = 'schedule-event';
					break;
				default:
					$action = 'schedules';
			}

			if ( is_rtl() ) {
				wp_enqueue_style(
					'ai1wm_servmask',
					Ai1wm_Template::asset_link( 'css/servmask.min.rtl.css' )
				);

				wp_enqueue_style(
					'ai1wmve_schedules',
					Ai1wm_Template::asset_link( 'css/' . $action . '.min.rtl.css', $this->plugin_prefix )
				);
			} else {
				wp_enqueue_style(
					'ai1wm_servmask',
					Ai1wm_Template::asset_link( 'css/servmask.min.css' )
				);

				wp_enqueue_style(
					'ai1wmve_schedules',
					Ai1wm_Template::asset_link( 'css/' . $action . '.min.css', $this->plugin_prefix )
				);
			}

			wp_enqueue_script(
				'ai1wmve_schedules',
				Ai1wm_Template::asset_link( 'javascript/' . $action . '.min.js', $this->plugin_prefix ),
				array( 'ai1wm_util' )
			);

			wp_localize_script(
				'ai1wmve_schedules',
				'ai1wmve_schedules',
				array(
					'ajax'       => array(
						'delete' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_schedule_event_delete' ) ),
						'log'    => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_schedule_event_log' ) ),
						'run'    => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_schedule_event_manual_run' ) ),
					),
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				)
			);

			wp_localize_script(
				'ai1wmve_schedules',
				'ai1wm_feedback',
				array(
					'ajax'       => array(
						'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
					),
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				)
			);

			wp_localize_script(
				'ai1wmve_schedules',
				'ai1wmve_schedules_options_locale',
				array(
					Ai1wmve_Schedule_Event::TYPE_EXPORT => array(
						'no_spam_comments'    => __( 'Do <strong>not</strong> export spam comments', AI1WM_PLUGIN_NAME ),
						'no_post_revisions'   => __( 'Do <strong>not</strong> export post revisions', AI1WM_PLUGIN_NAME ),
						'no_media'            => __( 'Do <strong>not</strong> export media library (files)', AI1WM_PLUGIN_NAME ),
						'no_inactive_themes'  => __( 'Do <strong>not</strong> export inactive themes (files)', AI1WM_PLUGIN_NAME ),
						'no_themes'           => __( 'Do <strong>not</strong> export themes (files)', AI1WM_PLUGIN_NAME ),
						'no_muplugins'        => __( 'Do <strong>not</strong> export must-use plugins (files)', AI1WM_PLUGIN_NAME ),
						'no_plugins'          => __( 'Do <strong>not</strong> export plugins (files)', AI1WM_PLUGIN_NAME ),
						'no_inactive_plugins' => __( 'Do <strong>not</strong> export inactive plugins (files)', AI1WM_PLUGIN_NAME ),
						'no_cache'            => __( 'Do <strong>not</strong> export cache (files)', AI1WM_PLUGIN_NAME ),
						'no_database'         => __( 'Do <strong>not</strong> export database (sql)', AI1WM_PLUGIN_NAME ),
						'no_email_replace'    => __( 'Do <strong>not</strong> replace email domain (sql)', AI1WM_PLUGIN_NAME ),
					),
					Ai1wmve_Schedule_Event::TYPE_IMPORT => array(),
				)
			);

			wp_localize_script(
				'ai1wmve_schedules',
				'ai1wmve_locale',
				array(
					'leave_feedback'                      => __( 'Leave plugin developers any feedback here', AI1WM_PLUGIN_NAME ),
					'how_may_we_help_you'                 => __( 'How may we help you?', AI1WM_PLUGIN_NAME ),
					'thanks_for_submitting_your_feedback' => __( 'Thanks for submitting your feedback!', AI1WM_PLUGIN_NAME ),
					'thanks_for_submitting_your_request'  => __( 'Thanks for submitting your request!', AI1WM_PLUGIN_NAME ),
					'want_to_delete_this_event'           => __( 'Are you sure you want to delete this event?', AI1WM_PLUGIN_NAME ),
					'want_to_start_this_event'            => __( 'Are you sure you want to start this event?', AI1WM_PLUGIN_NAME ),
					'event_log_modal_title'               => __( 'Event log', AI1WM_PLUGIN_NAME ),
					'event_log_no_records'                => __( 'There are no log records for this event', AI1WM_PLUGIN_NAME ),
					'close_modal'                         => __( 'Close', AI1WM_PLUGIN_NAME ),
					'button_done'                         => __( 'Done', $this->plugin_name ),
					'loading_placeholder'                 => __( 'Listing files ...', $this->plugin_name ),
					'selected_no_files'                   => __( 'No files selected', $this->plugin_name ),
					'selected_multiple'                   => __( '{x} files and {y} folders selected', $this->plugin_name ),
					'selected_multiple_folders'           => __( '{y} folders selected', $this->plugin_name ),
					'selected_multiple_files'             => __( '{x} files selected', $this->plugin_name ),
					'selected_one_file'                   => __( '{x} file selected', $this->plugin_name ),
					'selected_one_file_multiple_folders'  => __( '{x} file and {y} folders selected', $this->plugin_name ),
					'selected_one_file_one_folder'        => __( '{x} file and {y} folder selected', $this->plugin_name ),
					'selected_one_folder'                 => __( '{y} folder selected', $this->plugin_name ),
					'selected_multiple_files_one_folder'  => __( '{x} files and {y} folder selected', $this->plugin_name ),
					'column_name'                         => __( 'Name', $this->plugin_name ),
					'column_date'                         => __( 'Date', $this->plugin_name ),
					'legend_select'                       => __( 'Click checkbox to toggle selection', $this->plugin_name ),
					'legend_expand'                       => __( 'Click folder name to expand', $this->plugin_name ),
					'error_message'                       => __( 'Something went wrong, please refresh and try again', $this->plugin_name ),
					'button_clear'                        => __( 'Clear selection', $this->plugin_name ),
					'empty_list_message'                  => __( 'Folder empty. Click on folder icon to close it.', $this->plugin_name ),
					'column_table_name'                   => __( 'Table Name', $this->plugin_name ),
					'selected_no_tables'                  => __( 'No tables selected', $this->plugin_name ),
					'selected_one_table'                  => __( '{x} table selected', $this->plugin_name ),
					'selected_multiple_tables'            => __( '{x} tables selected', $this->plugin_name ),
					'database_name'                       => DB_NAME,
				)
			);

			wp_localize_script(
				'ai1wmve_schedules',
				'ai1wmve_file_exclude',
				array(
					'ajax' => array(
						'url'        => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmve_file_list' ) ),
						'list_nonce' => wp_create_nonce( 'ai1wmve_list' ),
					),
				)
			);

			wp_localize_script(
				'ai1wmve_schedules',
				'ai1wmve_site_list',
				array(
					'ajax'          => array(
						'sites_paginator' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wmme_sites_paginator' ) ),
					),
					'title'         => __( 'Subsite selection', AI1WM_PLUGIN_NAME ),
					'loading_sites' => __( 'Loading sites...', AI1WM_PLUGIN_NAME ),
					'button_all'    => __( 'Export entire network', AI1WM_PLUGIN_NAME ),
					'button_manual' => __( 'Select manually', AI1WM_PLUGIN_NAME ),
				)
			);
		}

		/**
		 * Enqueue scripts and styles for Reset Controller
		 *
		 * @param  string $hook Hook suffix
		 * @return void
		 */
		public function ai1wmve_enqueue_reset_scripts_and_styles( $hook ) {
			if ( stripos( 'all-in-one-wp-migration_page_ai1wmve_reset', $hook ) === false ) {
				return;
			}

			if ( is_rtl() ) {
				wp_enqueue_style(
					'ai1wm_export',
					Ai1wm_Template::asset_link( 'css/export.min.rtl.css' )
				);
				wp_enqueue_style(
					'ai1wm_backups',
					Ai1wm_Template::asset_link( 'css/backups.min.rtl.css' )
				);
				wp_enqueue_style(
					'ai1wm_reset',
					Ai1wm_Template::asset_link( 'css/reset-tools.min.rtl.css', $this->plugin_prefix )
				);
			} else {
				wp_enqueue_style(
					'ai1wm_export',
					Ai1wm_Template::asset_link( 'css/export.min.css' )
				);
				wp_enqueue_style(
					'ai1wm_backups',
					Ai1wm_Template::asset_link( 'css/backups.min.css' )
				);
				wp_enqueue_style(
					'ai1wm_reset',
					Ai1wm_Template::asset_link( 'css/reset-tools.min.css', $this->plugin_prefix )
				);
			}

			wp_enqueue_script(
				'ai1wm_reset',
				Ai1wm_Template::asset_link( 'javascript/reset-tools.min.js', $this->plugin_prefix ),
				array( 'ai1wm_util' )
			);

			wp_localize_script(
				'ai1wm_reset',
				'ai1wm_reset',
				array(
					'ajax'       => array(
						'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_reset' ) ) ),
					),
					'status'     => array(
						'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1, 'secret_key' => get_option( AI1WM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=ai1wm_status' ) ) ),
					),
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				)
			);

			wp_localize_script(
				'ai1wm_reset',
				'ai1wmve_locale',
				array(
					// Reset type agnostic translations
					'reset_in_progress'           => __( 'Reset In Progress', $this->plugin_name ),
					'reset_in_progress_info'      => __( 'Your request is being processed. This may take a few moments. Please do not close this window or navigate away from this page while the reset is in progress.', $this->plugin_name ),
					'stop_resetting_your_website' => __( 'You are about to stop resetting your website, are you sure?', $this->plugin_name ),
					'unable_to_stop_the_reset'    => __( 'Unable to stop the reset. Refresh the page and try again', $this->plugin_name ),
					'unable_to_start_the_reset'   => __( 'Unable to start the reset. Refresh the page and try again', $this->plugin_name ),
					'unable_to_reset'             => __( 'Unable to reset', $this->plugin_name ),
					'create_snapshot_title'       => __( 'Create a new snapshot', $this->plugin_name ),
					'create_snapshot_btn'         => __( 'Create snapshot', $this->plugin_name ),
					'cancel'                      => __( 'Cancel', $this->plugin_name ),
					'done'                        => __( 'Done', $this->plugin_name ),
					'stop'                        => __( 'Stop Reset', $this->plugin_name ),
					'close'                       => __( 'Close', $this->plugin_name ),
					'backup_btn'                  => __( 'Create Backup', $this->plugin_name ),
					'retry'                       => __( 'Retry', $this->plugin_name ),

					// Translations for each of a reset type
					'plugins'                     => array(
						'name'          => __( 'Plugin Purge', $this->plugin_name ),
						'description'   => __( 'Quickly removes all installed plugins from your WordPress site. Ideal for troubleshooting conflicts or starting fresh with plugin installations.', $this->plugin_name ),
						'help'          => __( 'This tool will remove all installed plugins from your site.', $this->plugin_name ),
						'reset_btn'     => __( 'Purge Plugins', $this->plugin_name ),
						'confirm_title' => __( 'Confirm Plugin Purge', $this->plugin_name ),
						'confirm_text'  => __( 'Are you sure you want to purge your plugins? This will delete all the plugins.', $this->plugin_name ),
						'confirm_btn'   => __( 'Purge Plugins', $this->plugin_name ),
					),
					'themes'                      => array(
						'name'          => __( 'Theme Reset', $this->plugin_name ),
						'description'   => __( 'Deletes all themes and reactivates the default WordPress theme. Useful for reverting to a clean state or resolving theme-related issues.', $this->plugin_name ),
						'help'          => __( 'This tool will delete all themes and revert to the default WordPress theme.', $this->plugin_name ),
						'reset_btn'     => __( 'Theme Reset', $this->plugin_name ),
						'confirm_title' => __( 'Confirm Theme Reset', $this->plugin_name ),
						'confirm_text'  => __( 'Are you sure you want to reset your themes? This will delete all your current themes and reactivate the default WordPress theme.', $this->plugin_name ),
						'confirm_btn'   => __( 'Theme Reset', $this->plugin_name ),
					),
					'media'                       => array(
						'name'          => __( 'Media Clean-Up', $this->plugin_name ),
						'description'   => __( 'Erases all media files from the site\'s media library. Ideal for clearing outdated or unnecessary media to declutter your site.', $this->plugin_name ),
						'help'          => __( 'This tool will delete all media files from your site\'s media library.', $this->plugin_name ),
						'reset_btn'     => __( 'Media Clean-Up', $this->plugin_name ),
						'confirm_title' => __( 'Confirm Media Clean-Up', $this->plugin_name ),
						'confirm_text'  => __( 'Are you sure you want to erase all media files from your site media library?', $this->plugin_name ),
						'confirm_btn'   => __( 'Media Clean-Up', $this->plugin_name ),
					),
					'database'                    => array(
						'name'          => __( 'Reset Database', $this->plugin_name ),
						'description'   => __( 'This action will permanently erase all existing data within your database and revert your WordPress site to its default state. This includes posts, pages, comments, settings, and user data. Useful for reverting to a clean state and starting fresh.', $this->plugin_name ),
						'help'          => __( 'This tool will delete all existing data within your database and revert your WordPress site to its default state.', $this->plugin_name ),
						'reset_btn'     => __( 'Reset Database', $this->plugin_name ),
						'confirm_title' => __( 'Confirm Database Reset', $this->plugin_name ),
						'confirm_text'  => __( 'Are you sure you want to reset your database? This action will permanently erase all existing data within your database and revert your WordPress site to its default state. This includes posts, pages, comments, settings, and user data. Once completed, this action cannot be undone.', $this->plugin_name ),
						'confirm_btn'   => __( 'Reset Database', $this->plugin_name ),
					),
					'all'                         => array(
						'name'          => __( 'Full Site Reset', $this->plugin_name ),
						'description'   => __( 'Completely resets the site, restoring WordPress to its initial installation state. Best for starting entirely from scratch or for a clean slate on the site.', $this->plugin_name ),
						'help'          => __( 'This tool will reset your entire WordPress site to its default installation state.', $this->plugin_name ),
						'reset_btn'     => __( 'Full Site Reset', $this->plugin_name ),
						'confirm_title' => __( 'Confirm Site Reset', $this->plugin_name ),
						'confirm_text'  => __( 'Are you sure you want to erase all media files from your site media library? This action is ideal for clearing outdated or unnecessary media to declutter your site.', $this->plugin_name ),
						'confirm_btn'   => __( 'Full Site Reset', $this->plugin_name ),
					),
				)
			);

			wp_enqueue_script(
				'ai1wm_backups',
				Ai1wm_Template::asset_link( 'javascript/backups.min.js' ),
				array( 'ai1wm_util' )
			);

			wp_localize_script(
				'ai1wm_backups',
				'ai1wm_feedback',
				array(
					'ajax'       => array(
						'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
					),
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				)
			);

			wp_localize_script(
				'ai1wm_backups',
				'ai1wm_import',
				array(
					'ajax'       => array(
						'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_import' ) ) ),
					),
					'status'     => array(
						'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1, 'secret_key' => get_option( AI1WM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=ai1wm_status' ) ) ),
					),
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				)
			);

			wp_localize_script(
				'ai1wm_backups',
				'ai1wm_export',
				array(
					'ajax'       => array(
						'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_export' ) ) ),
					),
					'status'     => array(
						'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1, 'secret_key' => get_option( AI1WM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=ai1wm_status' ) ) ),
					),
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				)
			);

			wp_localize_script(
				'ai1wm_backups',
				'ai1wm_backups',
				array(
					'ajax'       => array(
						'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_backups' ) ),
					),
					'backups'    => array(
						'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_backup_list' ) ),
					),
					'labels'     => array(
						'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_add_backup_label' ) ),
					),
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				)
			);

			wp_localize_script(
				'ai1wm_backups',
				'ai1wm_disk_space',
				array(
					'free'   => ai1wm_disk_free_space( AI1WM_STORAGE_PATH ),
					'factor' => AI1WM_DISK_SPACE_FACTOR,
					'extra'  => AI1WM_DISK_SPACE_EXTRA,
				)
			);

			wp_localize_script(
				'ai1wm_backups',
				'ai1wm_list',
				array(
					'ajax'       => array(
						'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_backup_list_content' ) ) ),
					),
					'download'   => array(
						'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_backup_download_file' ) ) ),
					),
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				)
			);

			wp_localize_script(
				'ai1wm_backups',
				'ai1wm_locale',
				array(
					'stop_exporting_your_website'         => __( 'You are about to stop exporting your website, are you sure?', $this->plugin_name ),
					'preparing_to_export'                 => __( 'Preparing to export...', $this->plugin_name ),
					'unable_to_export'                    => __( 'Unable to export', $this->plugin_name ),
					'unable_to_start_the_export'          => __( 'Unable to start the export. Refresh the page and try again', $this->plugin_name ),
					'unable_to_run_the_export'            => __( 'Unable to run the export. Refresh the page and try again', $this->plugin_name ),
					'unable_to_stop_the_export'           => __( 'Unable to stop the export. Refresh the page and try again', $this->plugin_name ),
					'please_wait_stopping_the_export'     => __( 'Please wait, stopping the export...', $this->plugin_name ),
					'close_export'                        => __( 'Close', $this->plugin_name ),
					'stop_export'                         => __( 'Stop export', $this->plugin_name ),
					'stop_importing_your_website'         => __( 'You are about to stop importing your website, are you sure?', $this->plugin_name ),
					'preparing_to_import'                 => __( 'Preparing to import...', $this->plugin_name ),
					'unable_to_import'                    => __( 'Unable to import', $this->plugin_name ),
					'unable_to_start_the_import'          => __( 'Unable to start the import. Refresh the page and try again', $this->plugin_name ),
					'unable_to_confirm_the_import'        => __( 'Unable to confirm the import. Refresh the page and try again', $this->plugin_name ),
					'unable_to_prepare_blogs_on_import'   => __( 'Unable to prepare blogs on import. Refresh the page and try again', $this->plugin_name ),
					'unable_to_stop_the_import'           => __( 'Unable to stop the import. Refresh the page and try again', $this->plugin_name ),
					'please_wait_stopping_the_import'     => __( 'Please wait, stopping the import...', $this->plugin_name ),
					'finish_import'                       => __( 'Finish', $this->plugin_name ),
					'close_import'                        => __( 'Close', $this->plugin_name ),
					'stop_import'                         => __( 'Stop import', $this->plugin_name ),
					'confirm_import'                      => __( 'Proceed', $this->plugin_name ),
					'confirm_disk_space'                  => __( 'I have enough disk space', $this->plugin_name ),
					'continue_import'                     => __( 'Continue', $this->plugin_name ),
					'please_do_not_close_this_browser'    => __( 'Please do not close this browser window or your import will fail', $this->plugin_name ),
					'leave_feedback'                      => __( 'Leave plugin developers any feedback here', $this->plugin_name ),
					'how_may_we_help_you'                 => __( 'How may we help you?', $this->plugin_name ),
					'thanks_for_submitting_your_feedback' => __( 'Thanks for submitting your feedback!', $this->plugin_name ),
					'thanks_for_submitting_your_request'  => __( 'Thanks for submitting your request!', $this->plugin_name ),
					'want_to_delete_this_file'            => __( 'Are you sure you want to delete this file?', $this->plugin_name ),
					'unlimited'                           => __( 'Restoring a backup is available via Unlimited extension. <a href="https://servmask.com/products/unlimited-extension" target="_blank">Get it here</a>', $this->plugin_name ),
					'restore_from_file'                   => __( '"Restore" functionality is available in a <a href="https://servmask.com/products/unlimited-extension" target="_blank">paid extension</a>.<br />You could also download the backup and then use "Import from file".', $this->plugin_name ),
					'out_of_disk_space'                   => __(
						'There is not enough space available on the disk.<br />' .
						'Free up %s of disk space.',
						$this->plugin_name
					),
					'backups_count_singular'              => __( 'You have %d backup', $this->plugin_name ),
					'backups_count_plural'                => __( 'You have %d backups', $this->plugin_name ),
					'archive_browser_error'               => __( 'Error', $this->plugin_name ),
					'archive_browser_list_error'          => __( 'Error while reading backup content', $this->plugin_name ),
					'archive_browser_download_error'      => __( 'Error while downloading file', $this->plugin_name ),
					'archive_browser_title'               => __( 'List the content of the backup', $this->plugin_name ),
					'progress_bar_title'                  => __( 'Reading...', $this->plugin_name ),
					'backup_encrypted'                    => __( 'The backup is encrypted', $this->plugin_name ),
					'backup_encrypted_message'            => __( 'Please enter a password to import the file', $this->plugin_name ),
					'submit'                              => __( 'Submit', $this->plugin_name ),
					'enter_password'                      => __( 'Enter a password', $this->plugin_name ),
					'repeat_password'                     => __( 'Repeat the password', $this->plugin_name ),
					'passwords_do_not_match'              => __( 'The passwords do not match', $this->plugin_name ),
				)
			);

			wp_enqueue_script(
				'ai1wmue_restore',
				Ai1wm_Template::asset_link( 'javascript/pro-restore.min.js', $this->plugin_prefix ),
				array( 'jquery' )
			);
		}

		/**
		 * Register initial router
		 *
		 * @return void
		 */
		public function ai1wmve_router() {
			add_action( 'wp_ajax_ai1wm_schedule_event_delete', 'Ai1wmve_Schedules_Controller::delete' );
			add_action( 'wp_ajax_ai1wm_schedule_event_log', 'Ai1wmve_Schedules_Controller::event_log' );
			add_action( 'wp_ajax_ai1wm_schedule_event_manual_run', 'Ai1wmve_Schedules_Controller::manual_run' );

			if ( current_user_can( 'export' ) ) {
				add_action( 'wp_ajax_ai1wmve_file_list', 'Ai1wmve_Export_Controller::list_files' );
				add_action( 'wp_ajax_ai1wm_reset', 'Ai1wmve_Reset_Controller::reset' );
			}

			$this->router();
		}

		/**
		 * Register initial parameters
		 *
		 * @return void
		 */
		public function ai1wmve_init() {
			if ( $this->purchase_id ) {
				$option = strtolower( $this->plugin_prefix ) . '_plugin_key';
				update_option( $option, $this->purchase_id );
			}
		}

		public static function ai1wmve_pro() {
			return Ai1wm_Template::get_content(
				'import/pro',
				array(),
				AI1WMVE_TEMPLATES_PATH
			);
		}

		/**
		 * Export and import buttons
		 */
		public function ai1wmve_buttons() {
			add_filter( 'ai1wmve_export_buttons_schedules', 'Ai1wmve_Schedules_Controller::buttons' );
			add_filter( 'ai1wm_pro', array( $this, 'ai1wmve_pro' ), 20 );

			$this->ai1wm_buttons();
		}

		/**
		 * Export and import buttons for extension
		 * @see ai1wmve_buttons
		 */
		public function ai1wm_buttons() {
		}

		/**
		 * Register listeners for filters
		 *
		 * @return void
		 */
		protected function ai1wmve_activate_filters() {
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 5, 2 );

			// Add compatibility check
			if ( $this->min_ai1wm_version ) {
				add_filter( 'ai1wm_export', array( $this, 'ai1wmve_compatibility_check' ), 10 );
				add_filter( 'ai1wm_import', array( $this, 'ai1wmve_compatibility_check' ), 10 );
				add_filter( 'ai1wm_reset', array( $this, 'ai1wmve_compatibility_check' ), 10 );
			}

			// Add reset commands
			add_filter( 'ai1wm_reset', 'Ai1wmve_Reset_Init::execute', 10 );
			add_filter( 'ai1wm_reset', 'Ai1wmve_Reset_Plugins::execute', 50 );
			add_filter( 'ai1wm_reset', 'Ai1wmve_Reset_Themes::execute', 100 );
			add_filter( 'ai1wm_reset', 'Ai1wmve_Reset_Media::execute', 150 );
			add_filter( 'ai1wm_reset', 'Ai1wmve_Reset_Database::execute', 200 );

			// Add file retention
			if ( $this->needs_file_retention() ) {
				add_filter( 'ai1wm_export', 'Ai1wmve_Export_Retention_File::execute', 270 );
			}

			$this->activate_filters();
		}

		/**
		 * Register extension's listeners for filters
		 *
		 * @return void
		 */
		protected function activate_filters() {
		}

		/**
		 * Register listeners for actions
		 *
		 * @return void
		 */
		protected function ai1wmve_activate_actions() {
			add_action( 'admin_init', array( $this, 'ai1wmve_init' ) );
			add_action( 'admin_init', array( $this, 'ai1wmve_load_textdomain' ) );
			add_action( 'admin_init', array( $this, 'ai1wmve_router' ) );
			add_action( 'admin_head', array( $this, 'ai1wmve_admin_head' ) );

			add_action( 'plugins_loaded', array( $this, 'ai1wmve_loaded' ), 20 );
			add_action( 'plugins_loaded', array( $this, 'ai1wmve_buttons' ), 20 );
			add_action( 'plugins_loaded', array( $this, 'ai1wm_commands' ), 20 );
			add_action( 'plugins_loaded', array( $this, 'ai1wmve_wp_cli' ), 20 );
			add_action( 'plugins_loaded', array( $this, 'wp_cli_extension' ), 30 );

			add_action( 'admin_enqueue_scripts', array( $this, 'ai1wmve_enqueue_export_scripts_and_styles' ), 20 );
			add_action( 'admin_enqueue_scripts', array( $this, 'ai1wmve_enqueue_import_scripts_and_styles' ), 20 );
			add_action( 'admin_enqueue_scripts', array( $this, 'ai1wmve_enqueue_backups_scripts_and_styles' ), 20 );
			add_action( 'admin_enqueue_scripts', array( $this, 'ai1wmve_enqueue_schedules_scripts_and_styles' ), 20 );

			// Enqueue reset scripts and styles
			add_action( 'admin_enqueue_scripts', array( $this, 'ai1wmve_enqueue_reset_scripts_and_styles' ), 5 );

			$this->activate_actions();
		}

		/**
		 * Max file size callback
		 *
		 * @return integer
		 */
		public function ai1wmve_max_file_size() {
			return AI1WMVE_MAX_FILE_SIZE;
		}

		public function ai1wmve_export_stats( $params ) {
			if ( ! isset( $params[ $this->plugin_params_key ] ) ) {
				return;
			}

			if ( isset( $params['ai1wm_manual_export'] ) ) {
				$this->ai1wmve_send_stats( 'export' );
			}
		}

		public function ai1wmve_import_stats( $params ) {
			if ( ! isset( $params[ $this->plugin_params_key ] ) ) {
				return;
			}

			if ( isset( $params['ai1wm_manual_restore'] ) ) {
				$this->ai1wmve_send_stats( 'restore' );
			}

			if ( isset( $params['ai1wm_manual_import'] ) ) {
				$this->ai1wmve_send_stats( 'import' );
			}
		}

		protected function ai1wmve_send_stats( $action ) {
			if ( $this->purchase_id ) {
				global $wpdb;

				$url = implode(
					'/',
					array(
						AI1WMVE_STATS_URL,
						$this->purchase_id,
						$action,
					)
				);

				wp_remote_post(
					$url,
					array(
						'timeout' => 5,
						'body'    => array(
							'url'           => get_site_url(),
							'email'         => get_option( 'admin_email' ),
							'wp_version'    => get_bloginfo( 'version' ),
							'php_version'   => PHP_VERSION,
							'mysql_version' => $wpdb->db_version(),
						),
					)
				);
			}
		}

		/**
		 * Outputs menu icon between head tags
		 *
		 * @return void
		 */
		public function ai1wmve_admin_head() {
			?>
			<style type="text/css" media="all">
				.ai1wm-label {
					border: 1px solid #5cb85c;
					background-color: transparent;
					color: #5cb85c;
					cursor: pointer;
					text-transform: uppercase;
					font-weight: 600;
					outline: none;
					transition: background-color 0.2s ease-out;
					padding: .2em .6em;
					font-size: 0.8em;
					border-radius: 5px;
					text-decoration: none !important;
				}

				.ai1wm-label:hover {
					background-color: #5cb85c;
					color: #fff;
				}
			</style>
			<?php
		}

		public function ai1wmve_admin_menu() {
			if ( ! defined( 'AI1WMVE_RESET_PAGE' ) ) {
				// Sub-level Reset
				add_submenu_page(
					'ai1wm_export',
					__( 'Reset Hub', $this->plugin_name ),
					__( 'Reset Hub', $this->plugin_name ),
					'import',
					'ai1wmve_reset',
					'Ai1wmve_Reset_Controller::index'
				);
				define( 'AI1WMVE_RESET_PAGE', true );
			}

			if ( ! defined( 'AI1WMVE_SCHEDULES_PAGE' ) ) {
				// Sub-level Schedules
				add_submenu_page(
					'ai1wm_export',
					__( 'Schedules', AI1WM_PLUGIN_NAME ),
					__( 'Schedules', AI1WM_PLUGIN_NAME ),
					'export',
					'ai1wmve_schedules',
					'Ai1wmve_Schedules_Controller::index'
				);
				define( 'AI1WMVE_SCHEDULES_PAGE', true );
			}
		}

		public function ai1wmve_compatibility_check( $params ) {
			if ( AI1WM_VERSION === 'develop' || version_compare( AI1WM_VERSION, $this->min_ai1wm_version, '>=' ) ) {
				return $params;
			}

			if ( defined( 'WP_CLI' ) ) {
				$message = __( 'All-in-One WP Migration is not the latest version. You must update the plugin before you can use it. ', AI1WM_PLUGIN_NAME );
			} else {
				$message = sprintf( __( '<strong>All-in-One WP Migration</strong> is not the latest version. <br />You must <a href="%s">update the plugin</a> before you can use it. <br />', AI1WM_PLUGIN_NAME ), network_admin_url( 'plugins.php' ) );
			}

			throw new Ai1wm_Compatibility_Exception( $message );
		}

		private function needs_file_retention() {
			// Check for Unlimited or Multisite
			if ( defined( 'AI1WMUE_PLUGIN_NAME' ) || defined( 'AI1WMME_PLUGIN_NAME' ) ) {
				return false;
			}

			// Check if we already added filter
			if ( has_filter( 'ai1wm_export', 'Ai1wmve_Export_Retention_File::execute' ) ) {
				return false;
			}

			return ai1wmve_is_running();
		}

		public function create_reset_backup_label( $params ) {
			if ( ! isset( $params['file'] ) ) {
				return;
			}

			// Set archive
			$archive = null;
			if ( isset( $params['archive'] ) ) {
				$archive = trim( $params['archive'] );
			}

			// Set reset label
			$reset_label = null;
			if ( ! empty( $params['ai1wm_reset_label'] ) ) {
				$reset_label = trim( $params['ai1wm_reset_label'] );
			}

			if ( isset( $archive, $reset_label ) ) {
				switch ( $reset_label ) {
					case 'plugins':
						$reset_type = __( 'Plugin Purge', AI1WM_PLUGIN_NAME );
						break;

					case 'themes':
						$reset_type = __( 'Theme Reset', AI1WM_PLUGIN_NAME );
						break;

					case 'media':
						$reset_type = __( 'Media Clean-Up', AI1WM_PLUGIN_NAME );
						break;

					case 'database':
						$reset_type = __( 'Reset Database', AI1WM_PLUGIN_NAME );
						break;

					default:
						$reset_type = __( 'Full Site Reset', AI1WM_PLUGIN_NAME );
				}

				try {
					Ai1wm_Backups::set_label( $archive, sprintf( __( 'Before %s - %s', AI1WM_PLUGIN_NAME ), $reset_type, date_i18n( 'F d, Y, h:i A' ) ) );
				} catch ( Ai1wm_Backups_Exception $e ) {
				}
			}
		}
	}
}

<?php
/**
 * Plugin Name: Plan Your Visit
 * Plugin URI: https://churchhero.com/
 * Description: Adds required code for Plan Your Visit
 * Version: 1.0.0
 * Author: AndrewRMinion Design
 * Author URI: https://andrewrminion.com
 * Copyright: 2018 AndrewRMinion Design

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package PLanYourVisit
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Main class
 */
class Plan_Your_Visit {

	/**
	 * Plugin version
	 *
	 * @access  private
	 *
	 * @var  string Plugin version.
	 */
	private $version = '1.0.0';

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @since  1.0.0
	 *
	 * @return Plan_Your_Visit class.
	 */
	public static function get_instance() : Plan_Your_Visit {
		if ( null === self::$instance ) {
			self::$instance = new Plan_Your_Visit();
		}

		return self::$instance;
	}

	/**
	 * Kick things off.
	 *
	 * @since  1.0.0r
	 *
	 * @access  private
	 *
	 * @return  void Adds WP actions.
	 */
	private function __construct() {
		// Activation hooks.
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Admin menu and page.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );

		// Ajax actions.
		add_action( 'wp_ajax_plan_your_visit_save_key', array( $this, 'save_key' ) );

		// Frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_filter( 'script_loader_tag', array( $this, 'force_async' ), 99, 2 );
	}

	/**
	 * Get plugin URL with optional path appended.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $path Path to append.
	 *
	 * @return string       URL to plugin with path appended.
	 */
	private function plugin_dir_url( $path = '' ) {
		return plugin_dir_url( __FILE__ ) . $path;
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @since  1.0.0
	 *
	 * @return void Deletes authorization option.
	 */
	public function deactivate() {
		delete_option( 'plan_your_visit_authorization' );
	}

	/**
	 * Enqueue frontend scripts.
	 *
	 * @since  1.0.0
	 *
	 * @return void Enqueues scripts.
	 */
	public function frontend_scripts() {
		$authorization_key = get_option( 'plan_your_visit_authorization' );

		if ( ! empty( $authorization_key ) ) {
			wp_enqueue_script( 'plan-your-visit', 'https://api.churchhero.com/pyv-install', array(), $this->version, true );
			wp_add_inline_script( 'plan-your-visit', 'window.PlanYourVisit={id:"' . esc_attr( $authorization_key ) . '"};', 'before' );
		}
	}

	/**
	 * Add async attribute if the path contains this plugin directory.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $tag    HTML <script> tag.
	 * @param  string $handle WordPress’ internal handle for this script.
	 *
	 * @return string         HTML <script> tag.
	 */
	public function force_async( string $tag, string $handle ) {
		if ( strpos( $tag, 'api.churchhero.com' ) !== false ) {
			$tag = str_replace( ' src', ' async="async" src', $tag );
		}
		return $tag;
	}

	/**
	 * Register backend assets.
	 *
	 * @since  1.0.0
	 *
	 * @return void Registers scripts and styles.
	 */
	public function register_assets() {
		wp_register_script( 'plan-your-visit-backend', $this->plugin_dir_url( 'assets/plan-your-visit.js' ), array( 'jquery' ), $this->version, false );
		wp_register_style( 'plan-your-visit-backend', $this->plugin_dir_url( 'assets/plan-your-visit.css' ), array(), $this->version );
	}

	/**
	 * Add menu item.
	 *
	 * @since  1.0.0
	 *
	 * @return void Adds admin menu item.
	 */
	public function admin_menu() {
		add_menu_page( 'Plan Your Visit', 'Plan Your Visit', 'manage_options', 'plan-your-visit', array( $this, 'login_page' ), $this->plugin_dir_url( 'assets/plan-your-visit-icon.png' ) );
	}

	/**
	 * Add admin page content.
	 *
	 * @since  1.0.0
	 *
	 * @return void Prints HTML content.
	 */
	public function login_page() {
		wp_enqueue_script( 'plan-your-visit-backend' );
		wp_enqueue_style( 'plan-your-visit-backend' );
		?>
		<div class="wrap plan-your-visit">
			<p><img src="<?php echo esc_url( $this->plugin_dir_url( 'assets/church-hero.png' ) ); ?>" alt="Church Hero" class="logo church-hero" /></p>
			<p>Enter your Church Hero login information below to install Plan&nbsp;Your&nbsp;Visit on your website.</p>
			<p><img src="<?php echo esc_url( $this->plugin_dir_url( 'assets/plan-your-visit.png' ) ); ?>" alt="Plan Your Visit" class="logo plan-your-visit" /></p>
			<form method="post" id="church-hero-login" action="https://api.churchhero.com/pyv-auth">
				<p class="message">
					<?php
					if ( ! empty( get_option( 'plan_your_visit_authorization' ) ) ) {
						echo 'Your authorization key has been saved; enter new credentials to change your account or deactivate the plugin to clear it.';
					}
					?>
				</p>
				<input type="email" name="email" placeholder="john.doe@example.com" value="<?php echo esc_attr( get_option( 'plan_your_visit_email' ) ); ?>" />
				<input type="password" name="password" placeholder="••••••••••" />
				<input type="hidden" name="action" value="plan_your_visit_save_key" />
				<input type="hidden" name="authorization_key" value="<?php echo esc_attr( get_option( 'plan_your_visit_authorization' ) ); ?>" />
				<?php wp_nonce_field( 'plan_your_visit_save_key', 'pyv_nonce' ); ?>
				<input type="submit" value="Install Plan Your Visit" />
			</form>
		</div>
		<?php
	}

	/**
	 * Save authorization key to database.
	 *
	 * @since  1.0.0
	 *
	 * @return void Echos bool whether option was updated or not and dies.
	 */
	public function save_key() {
		if ( wp_verify_nonce( $_POST['nonce'], 'plan_your_visit_save_key' ) ) {
			if ( wp_unslash( $_POST['key'] ) === get_option( 'plan_your_visit_authorization' ) ) {
				$result = true;
			} else {
				$result = update_option( 'plan_your_visit_email', esc_attr( wp_unslash( $_POST['email'] ) ) ); // WPCS: XSS ok.
				$result = update_option( 'plan_your_visit_authorization', esc_attr( wp_unslash( $_POST['key'] ) ) ); // WPCS: XSS ok.
			}
		}

		wp_die( $result ); // WPCS: XSS ok.
	}

}

Plan_Your_Visit::get_instance();

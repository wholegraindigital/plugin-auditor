<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Plugin_Audit_Settings")) :

class Plugin_Audit_Settings {

	public static $default_settings = array(
		'allowed_id' => 0,
		'content_frozen' => 0,
	);

	var $pagehook, $page_id, $settings_field, $options;

	function __construct() {
		$this->page_id = 'plugin_audit';
		// This is the get_options slug used in the database to store our plugin option values.
		$this->settings_field = 'plugin_audit_options';
		$this->options = get_option($this->settings_field);

		if (is_admin()) {
			add_action('admin_menu', array($this, 'admin_menu'), 20);
		}
	}

	function admin_menu() {
		if (! current_user_can('update_plugins'))
			return;

		// Add a new submenu to the standard Settings panel
		$this->pagehook = $page = add_plugins_page(__('Plugin Audit', 'plugin_audit'), __('Audit', 'plugin_audit'), 'administrator', $this->page_id, array($this,'render'));

		// Include js, css, or header *only* for our settings page
		add_action("admin_print_scripts-$page", array($this, 'js_includes'));

		// add_action("admin_print_styles-$page", array($this, 'css_includes'));
		add_action("admin_head-$page", array($this, 'admin_head'));
	}

	function admin_head() {
?>
		<style>
			.settings_page_plugin_audit label { display:inline-block; width: 150px; }
		</style>
<?php
	}

	function js_includes() {
		// Needed to allow metabox layout and close functionality.
		wp_enqueue_script('postbox');
	}

	/*
		Sanitize our plugin settings array as needed.
	*/
	function sanitize_theme_options($options) {
		$options['example_text'] = stripcslashes($options['example_text']);
		return $options;
	}

	/*
		Settings access functions.
	*/
	protected function get_field_name($name) {

		return sprintf('%s[%s]', $this->settings_field, $name);
	}

	protected function get_field_id($id) {

		return sprintf('%s[%s]', $this->settings_field, $id);
	}

	protected function get_field_value($key) {

		return $this->options[$key];
	}

	/*
		Render settings page.
	*/
	function render() {
		global $wp_meta_boxes, $wpdb;

		$title = __('Plugin Audit', 'plugin_audit');

		$table_name = $wpdb->prefix . 'plugin_audit';
		$logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY `timestamp` DESC");
		?>
		<div class="wrap">
			<h2><?php echo esc_html($title); ?></h2>

            <table class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column">User</th>
                        <th scope="col" class="manage-column">Action</th>
                        <th scope="col" class="manage-column">Note</th>
                        <th scope="col" class="manage-column">Plugin</th>
                        <th scope="col" class="manage-column">WP Version</th>
                        <th scope="col" class="manage-column">Timestamp</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column">User</th>
                        <th scope="col" class="manage-column">Action</th>
                        <th scope="col" class="manage-column">Note</th>
                        <th scope="col" class="manage-column">Plugin</th>
                        <th scope="col" class="manage-column">WP Version</th>
                        <th scope="col" class="manage-column">Timestamp</th>
                    </tr>
                </tfoot>
                <tbody id="the-list">
                	<?php
                    foreach($logs as $log) {
                        $plugin_data = json_decode($log->plugin_data);
                        $old_plugin_data = json_decode($log->old_plugin_data);
                    ?>
                    <tr id="log-<?php echo $log->id ?>" class="log-<?php echo $log->id ?> type-log action-<?php echo $log->action ?>">
                        <td><?php $user_info = get_userdata($log->user_id); if($user_info) echo $user_info->user_login . ' (' . implode(', ', $user_info->roles ? $user_info->roles : array()) . ')' ?></td>
                        <td><?php echo $log->action ?></td>
                        <td><?php echo $log->note ?></td>
                        <td>
                            <span title="<?php echo $this->print_title($plugin_data) ?>"><?php echo $plugin_data->Name ?>
                            (<?php echo $log->action=='version change' ? $old_plugin_data->Version.' -> '.$plugin_data->Version : $plugin_data->Version ?>)
                            </span>
                        </td>
                        <td><?php echo $log->wp_version ?></td>
                        <td><?php echo $log->timestamp ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
		</div>
	<?php }

    function print_title($var) {
        $search = array('stdClass Object', '<', '>', '"');
        return trim(trim(str_replace($search, '', strip_tags(print_r($var, true)))), '()');
    }

} // end class
endif;
?>
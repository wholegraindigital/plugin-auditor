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
        $this->pagehook = $page = add_plugins_page(__('Plugin Auditor', 'plugin_audit'), __('Plugin Auditor', 'plugin_audit'), 'administrator', $this->page_id, array($this,'render'));

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

            // Sort table attributes
            $handle = 'sortabble';
            $src = plugins_url() . '/plugin-auditor/assets/js/sorttable.js';
            $deps = array();
            $ver = '2.0.0';
            $in_footer = true;

            // Enqueue JavaScript to sort tables
            wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );

            // Plugin styles
            $handle = 'main';
            $src = plugins_url() . '/plugin-auditor/assets/css/main.css';
            $deps = array();
            $ver = '0.2';
            $media = 'all';

            // Enqueue CSS to sort tables
            wp_enqueue_style( $handle, $src, $deps, $ver, $media );


         ?>
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

        $table_name = $wpdb->prefix . 'plugin_audit';
        $logs = $wpdb->get_results("SELECT * FROM $table_name WHERE `action` = \"installed\" ORDER BY `timestamp` DESC ");

        if(isset($_POST['add_note'])) {
            if(!empty($_POST['note'])) {
                $wpdb->update(
                    $table_name,
                    array('note' => $_POST['note']),
                    array('id' => intval($_POST['log_id'])),
                    array('%s'),
                    array('%d')
                );
            }
        }

        ?>
        
        <div class="wrap">

            <table class="sortable wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column">User</th>
                        <th scope="col" class="manage-column">Action</th>
                        <th scope="col" class="manage-column">Note</th>
                        <th scope="col" class="manage-column">Plugin</th>
                        <th scope="col" class="manage-column">WP Version</th>
                        <th scope="col" class="manage-column">Timestamp</th>
                        <th scope="col" class="manage-column sorttable_nosort">Manage Comments</th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php
                    foreach($logs as $log) {
                        $plugin_data = json_decode($log->plugin_data);
                        $old_plugin_data = json_decode($log->old_plugin_data);
                    ?>
                    <tr id="log-<?php echo $log->id ?>" class="log-<?php echo $log->id ?> type-log action-<?php echo $log->action ?>">
                        <td><?php $user_info = get_userdata($log->user_id); 

                        if($user_info->first_name && $user_info->last_name):
                            echo $user_info->first_name . ' ' . $user_info->last_name;
                        elseif ($user_info->first_name && !$user_info->last_name):
                            echo $user_info->first_name;
                        else:
                            echo $user_info->user_login;
                        endif;
                        ?></td>

                        <td><?php echo $log->action ?></td>
                        <td data-js="note"><?php echo $log->note ?></td>
                        <td>
                            <span title="<?php echo $this->print_title($plugin_data) ?>"><?php echo $plugin_data->Name ?>
                            (<?php echo $log->action=='version change' ? $old_plugin_data->Version.' -> '.$plugin_data->Version : $plugin_data->Version ?>)
                            </span>
                        </td>
                        <td><?php echo $log->wp_version ?></td>
                        <td><?php echo $log->timestamp ?></td>
                        <td>
                            <form action="" method="post">
                                <input type="hidden" name="log_id" value="<?php echo $log->id ?>">
                                <input type="hidden" name="edit_note" value="true">

                                <button type="submit" class="button button-primary add-or-edit-comment" style="vertical-align: top;">
                                <?php echo __($log->note == NULL ? 'Add comment' : 'Edit comment', 'plugin_audit')
                                ?>
                                </button>
                            </form>
                        </td>
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
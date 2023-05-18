<?php
/**
 * Plugin Name:     More Sql Connection
 * Description:     This is plugin support connecting to SQL. Service : MYSQL, POSTGRESQL
 * Author:          LF GLOBAL TECH
 * Text Domain:     more-sql-connection
 * Domain Path:     /languages
 * Version:         0.1.1
 *
 * @package         More_Sql_Connection
 */

define('MORE_SQL_DIR', __DIR__);

define('MORE_SQL_S_ACTIVE', 1);
define('MORE_SQL_S_1', 'active');
define('MORE_SQL_S_DEACTIVE', 0);
define('MORE_SQL_S_0', 'deactive');
define('MORE_SQL_NOTICE_S_0', 'error');
define('MORE_SQL_NOTICE_S_1', 'success');

define('MORE_SQL_CAPABILITY_CAN_SHOW', 'can_show_more_sql_capability');

require_once(MORE_SQL_DIR . '/connection.php');

foreach (glob(MORE_SQL_DIR . "/lib/*.php") as $file_name) {
    require_once($file_name);
}
class More_Sql_Connection
{

    private $textDomain = 'more-sql-connection';
    public $connecting_slug = 'more-sql-connection/connecting.php';
    public $plugin_dir = WP_PLUGIN_DIR . '/more-sql-connection';
    public $notice_message = '';
    public $notice_status = 'success';

    public $hash = null;

    public function __construct()
    {

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $this->hash = new LF_Hash(AUTH_KEY);
        register_activation_hook(__FILE__, [$this, 'pluginprefix_activate']);
        register_deactivation_hook(__FILE__, [$this, 'pluginprefix_deactivate']);

        add_action('admin_enqueue_scripts', [$this, 'more_sql_enqueue_styles']);

        add_action('admin_menu', [$this, 'more_sql_register_menu_page']);

        add_action('admin_notices', [$this, 'more_sql_admin_notice']);

        $users = new WP_User(get_current_user_id());

        // Register action function, api
        $this->set_admin_api();
    }

    private function set_admin_api()
    {
        $listFunction = ['add_connection', 'test_connection', 'toggle_status', 'delete_connection', 'update_connection', 'list-connection'];

        foreach ($listFunction as $function) {
            add_action('admin_post_nopriv_' . $function, [$this, $function]);
            add_action('admin_post_' . $function, [$this, $function]);
        }
    }

    // Call at active the plugin
    public function pluginprefix_activate()
    {
        $this->more_sql_create_db();
    }

    // Call at deactive the plugin
    public function pluginprefix_deactivate()
    {
        $this->more_sql_remove_db();
    }

    // The table already exists 
    private function is_table_existed($table_name)
    {
        global $wpdb;

        foreach ($wpdb->get_col('SHOW TABLES', 0) as $table) {
            if ($table === $table_name) {
                return true;
            }
        }

        return false;
    }

    private function more_sql_remove_db()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'more_sql';
        if ($this->is_table_existed($table_name)) {
            $sql = "DROP TABLE IF EXISTS $table_name";
            $wpdb->query($sql);
        }

    }


    // Create More SQL table
    private function more_sql_create_db()
    {

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'more_sql';

        $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        name VARCHAR(50),
        drive VARCHAR(50),
        host VARCHAR(255),
        port VARCHAR(10),
        db_username VARCHAR(255),
        db_name VARCHAR(255),
        status int DEFAULT 0,
		connection_string JSON,
        create_by int,
        create_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		UNIQUE KEY id (id) ) $charset_collate;";

        maybe_create_table($table_name, $sql);
    }

    function more_sql_enqueue_styles($hook_suffix)
    {
        if ($this->connecting_slug == $hook_suffix) {
            wp_enqueue_script(
                'connecting-js', // name your script so that you can attach other scripts and de-register, etc.
                WP_PLUGIN_URL . '/more-sql-connection/dist/js/connecting.js',
                // this is the location of your script file
                array('jquery') // this array lists the scripts upon which your script depends
            );
            wp_enqueue_script(
                'table-js', // name your script so that you can attach other scripts and de-register, etc.
                WP_PLUGIN_URL . '/more-sql-connection/dist/js/table.js',
                // this is the location of your script file
                array('jquery') // this array lists the scripts upon which your script depends
            );

            wp_localize_script('connecting-js', 'connecting_obj', ['ajax_url' => admin_url('admin-post.php'), 'nonce' => wp_create_nonce('add_connection')]);
        }

    }


    /**
     * Register a custom menu page.
     */
    public function more_sql_register_menu_page()
    {
        if (!current_user_can('edit_plugins')) {
            return;
        }
        add_menu_page(
            __('More SQL Connection', $this->textDomain),
            'SQL Connection',
            'manage_options',
            $this->connecting_slug,
            '',
            'dashicons-database-add',
            6
        );
    }

    public function add_connection()
    {
        if (!isset($_POST['_wpnonce_add_connect']) || !wp_verify_nonce($_POST['_wpnonce_add_connect'], 'add_connect')) {
            error_log('Sorry, your nonce did not verify.');
            exit;
        } else {

            global $wpdb;
            $table_name = $wpdb->prefix . 'more_sql';

            if (!$this->check_table_exists($table_name)) {
                error_log('Sorry, The table ' . $table_name . ' not exists');
            }

            $port = $_POST['port'];
            $host_name = $_POST['host_name'];
            $db_name = $_POST['db_name'];

            $connect_pdo_string = [$_POST['sql_drive'] . ':host=' . $host_name, 'port=' . $port, 'dbname=' . $db_name];
            $connection_string = [
                'drive' => $_POST['sql_drive'],
                'host' => $host_name,
                'port' => $port,
                'db_user' => $_POST['db_user'],
                'db_password' => $this->hash->encrypt($_POST['db_pass']),
                'db_name' => $db_name,
                'connect_string' => implode(';', $connect_pdo_string)
            ];

            $data = [
                'name' => $_POST['connection_name'],
                'connection_string' => wp_json_encode($connection_string),
                'drive' => $_POST['sql_drive'],
                'host' => $host_name,
                'port' => $port,
                'db_username' => $_POST['db_user'],
                'db_name' => $db_name,
                'create_by' => get_current_user_id(),
                'create_at' => current_datetime()->format('Y-m-d H:i:s')
            ];

            $wpdb->insert($table_name, $data, null);

            wp_redirect(get_site_url(null, $_POST['_wp_http_referer']));
        }
    }

    public function update_connection()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'more_sql';

        $port = $_POST['port'];
        $host_name = $_POST['host_name'];
        $db_name = $_POST['db_name'];
        $connection_id = $_POST['id'];

        $connect_pdo_string = [$_POST['sql_drive'] . ':host=' . $host_name, 'port=' . $port, 'dbname=' . $db_name];
        $connection_string = [
            'drive' => $_POST['sql_drive'],
            'host' => $host_name,
            'port' => $port,
            'db_user' => $_POST['db_user'],
            'db_password' => $this->hash->encrypt($_POST['db_pass']),
            'db_name' => $db_name,
            'connect_string' => implode(';', $connect_pdo_string)
        ];

        $data = [
            'name' => $_POST['connection_name'],
            'connection_string' => wp_json_encode($connection_string),
            'drive' => $_POST['sql_drive'],
            'host' => $host_name,
            'port' => $port,
            'db_username' => $_POST['db_user'],
            'db_name' => $db_name,
            'create_by' => get_current_user_id(),
            'create_at' => current_datetime()->format('Y-m-d H:i:s')
        ];

        $wpdb->update($table_name, $data, ['id' => $connection_id]);
        $this->set_notices(get_site_url(null, $_POST['_wp_http_referer']), 'The connection updated!', 0);

    }

    public function delete_connection($id = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'more_sql';

        if ($id == null && $_GET['item_id']) {
            $connection_id[] = $_GET['item_id'];
        } elseif (is_array($id)) {
            $connection_id = $id;
        } else {
            $connection_id[] = $id;
        }

        $ids = implode(',', $connection_id);

        if ($wpdb->query("DELETE FROM {$table_name} WHERE ID IN($ids)")) {
            $this->set_notices(wp_get_referer(), 'The connection deleted!');
        } else {
            $this->set_notices(wp_get_referer(), 'The connection not been delete!', 0);
        }
    }

    public function toggle_status()
    {
        if (!isset($_GET['action_name'])) {
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'more_sql';
        $item_id = $_GET['item_id'];

        $status = MORE_SQL_S_ACTIVE;

        if ($_GET['action_name'] == MORE_SQL_S_1) {
            $ids = $wpdb->get_col("SELECT * FROM {$table_name} WHERE status=1 AND id <> {$item_id}");
            if (count($ids) > 0) {
                $this->update_status(array_map('intval', $ids), MORE_SQL_S_DEACTIVE);
            }

            $status = MORE_SQL_S_ACTIVE;
        } else {
            $status = MORE_SQL_S_DEACTIVE;
        }

        if ($this->update_status($item_id, $status)) {
            $this->set_notices(wp_get_referer(), 'Status updated!');
        } else {
            $this->set_notices(wp_get_referer(), 'Status not been update!', 0);
        }

    }


    function wpdb_update($table, $data, $where)
    {
        global $wpdb;

        if (!is_array($data) || !is_array($where)) {
            return false;
        }

        $SET = [];
        foreach ($data as $field => $value) {
            $field = sanitize_key($field);

            if (is_null($value)) {
                $SET[] = "`$field` = NULL";
                continue;
            }

            $SET[] = $wpdb->prepare("`$field` = %s", $value);
        }

        $WHERE = [];
        foreach ($where as $field => $value) {
            $field = sanitize_key($field);

            if (is_null($value)) {
                $WHERE[] = "`$field` IS NULL";
                continue;
            }

            if (is_array($value)) {
                foreach ($value as &$val) {
                    $val = $wpdb->prepare("%s", $val);
                }
                unset($val);

                $WHERE[] = "`$field` IN (" . implode(',', $value) . ")";
            } else {
                $WHERE[] = $wpdb->prepare("`$field` = %s", $value);
            }
        }

        $sql = "UPDATE `$table` SET " . implode(', ', $SET) . " WHERE " . implode(' AND ', $WHERE);

        return $wpdb->query($sql);
    }

    public function get_row_id($id = null, array $where = [])
    {
        $id_connection = $id;
        if ($id == null && isset($_GET['item_id'])) {
            $id_connection = $_GET['item_id'];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'more_sql';
        $conditions = [];

        if ($id_connection != null) {
            $conditions = ['id' => $id_connection];
        }

        if ($where != null) {
            $conditions = array_merge($where, $conditions);
        }

        $final_cond = [];

        foreach ($conditions as $key => $condition) {
            array_push($final_cond, $key . ' = ' . $condition);
        }

        $conn_row = $wpdb->get_row("SELECT * FROM {$table_name} WHERE " . implode(' AND ', $final_cond));
        $connect_string = json_decode($conn_row->connection_string);
        $db_password = $this->hash->decrypt($connect_string->db_password);

        return new Connection($conn_row->id, $conn_row->name, $conn_row->drive, $conn_row->host, $conn_row->port, $conn_row->db_username, $db_password, $conn_row->db_name, $conn_row->status);
    }

    public function update_status($id, $status)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'more_sql';

        $data = ['status' => $status];
        $where = ['id' => $id];

        if ($this->wpdb_update($table_name, $data, $where)) {
            return true;
        }
        return false;
    }

    public function list_connection()
    {
    }

    /**
     * Get the actived connection
     *
     * @return Connection
     **/
    public function get_connect_actived()
    {
        if ($this->count_connection() == 0) {
            wp_die(sprintf("Couldn\'t find any connection! <a href='%s'>Click to add a connection</a>", admin_url('admin.php?page=more-sql-connection/connecting.php')));
        }
        return $this->get_row_id(null, ['status' => 1]);
    }

    public function test_connection()
    {
        $drive = $_POST['drive'];
        $servername = $_POST['host'];
        $username = $_POST['db_user'];
        $password = $_POST['db_password'];
        $dbname = $_POST['db_name'];
        $port = $_POST['port'];

        try {
            $conn = new PDO("$drive:host=$servername;port=$port;dbname=$dbname", $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->json_response('', 'Connected successfully');
        } catch (PDOException $e) {
            $this->json_response('', "Connection failed: " . $e->getMessage(), 500);
        }
    }

    private function json_response($data, $message = '', $code = 200)
    {
        $responce = ['code' => $code, 'message' => $message, 'data' => $data];
        echo json_encode($responce);
        exit();
    }

    // Check table is exists
    public function check_table_exists($table_name)
    {
        global $wpdb;
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));

        if (!$wpdb->get_var($query) == $table_name) {
            return false;
        }

        return true;

    }

    // Count record
    public function count_connection()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'more_sql';

        $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM  $table_name");

        return $rowcount;
    }

    public function set_notices($location, $message, $type = 1)
    {

        if ($type == 1) {
            $this->notice_status = MORE_SQL_NOTICE_S_1;
        } else {
            $this->notice_status = MORE_SQL_NOTICE_S_0;
        }

        $this->notice_message = $message;
        wp_safe_redirect(wp_get_referer() . '&coderesp=' . $this->notice_status . '&messresp=' . $message);
    }

    public function more_sql_admin_notice()
    {
        global $hook_suffix;

        if ($hook_suffix != $this->connecting_slug) {
            return;
        }

        if (!isset($_GET['coderesp']) || !isset($_GET['messresp'])) {
            return;
        }
        ?>
        <div class="notice notice-<?= $_GET['coderesp'] ?> is-dismissible">
            <p>
                <?= _e($_GET['messresp'], 'more-sql-connection') ?>
            </p>
        </div>

        <?php
    }


}

// $moreSqlConnection = new More_Sql_Connection();

if (!function_exists('more_sql_render_text_field')) {
    function more_sql_render_text_field($name, $label, $value = '', $description = '', $is_required = false)
    {
        $class_wrap = ['form-field', $name . '-wrap'];
        $required = '';
        if ($is_required) {
            array_push($class_wrap, 'form-required');
            $required = 'required';
        }

        printf('<div class="%s">', implode(' ', $class_wrap));
        printf('<label for="%1$s">%2$s</label>', $name, $label);
        printf('<input name="%1$s" id="%1$s" type="text" value="%2$s" size="40" %3$s>', $name, $value, $required);
        if ($description != '') {
            printf('<p id="%1$s_description" class="%1$s-description">%2$s</p>', $name, $description);
        }
        printf('</div>');
    }
}

if (!function_exists('more_sql_render_password_field')) {
    function more_sql_render_password_field($name, $label, $value = '', $description = '', $is_required = false)
    {
        $class_wrap = ['form-field', $name . '-wrap'];
        $required = '';
        if ($is_required) {
            array_push($class_wrap, 'form-required');
            $required = 'required';
        }

        printf('<div class="%s">', implode(' ', $class_wrap));
        printf('<label for="%1$s">%2$s</label>', $name, $label);
        printf('<input name="%1$s" id="%1$s" type="password" value="%2$s" size="40" %3$s>', $name, $value, $required);
        if ($description != '') {
            printf('<p id="%1$s_description" class="%1$s-description">%2$s</p>', $name, $description);
        }
        printf('</div>');
    }
}
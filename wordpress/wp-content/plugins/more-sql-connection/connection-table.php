<?php

require_once MORE_SQL_DIR . '/more-sql-connection.php';

class Connection_Tablet extends WP_List_Table
{
    
    private $table_data;
    private $more_sql_connection;

    public function __construct($array = array())
    {

        $this->more_sql_connection = new More_Sql_Connection();

        parent::__construct($array);

    }

    // Define table columns
    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'name' => __('Name', 'more-sql-connection'),
            'host' => __('Host', 'more-sql-connection'),
            'port' => __('Port', 'more-sql-connection'),
            'drive' => __('Drive', 'more-sql-connection'),
            'db_username' => __('DB Username', 'more-sql-connection'),
            'db_name' => __('DB Name', 'more-sql-connection'),
            'status' => __('Status', 'more-sql-connection')
        );
        return $columns;
    }

    // Bind table with columns, data and all
    public function prepare_items()
    {
        //data
        if (isset($_POST['s'])) {
            $this->table_data = $this->get_table_data($_POST['s']);
        } else {
            $this->table_data = $this->get_table_data();
        }

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $primary = 'name';
        $this->_column_headers = array($columns, $hidden, $sortable, $primary);
        $this->process_bulk_action();
        $this->items = $this->table_data;
    }

    // Get table data
    private function get_table_data($search = '')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'more_sql';

        if (!empty($search)) {
            return $wpdb->get_results(
                "SELECT * from {$table} WHERE name Like '%{$search}%' OR host Like '%{$search}%' OR port Like '%{$search}%' OR drive Like '%{$search}%' OR db_username Like '%{$search}%' OR db_name Like '%{$search}%'",
                ARRAY_A
            );
        } else {
            return $wpdb->get_results(
                "SELECT * from {$table}",
                ARRAY_A
            );
        }
    }

    // To show bulk action dropdown
    function get_bulk_actions()
    {
        $actions = array(
            'delete_all' => __('Delete', 'supporthost-admin-table')
        );
        return $actions;
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
            case 'host':
            case 'port':
            case 'drive':
            case 'db_username':
            case 'db_name':
            case 'status':
            default:
                return $item[$column_name];
        }
    }

    function column_name($item)
    {
        $action = 'active';
        if ($item['status'] == 1) {
            $action = 'deactive';
        }

        $actions = array(
            $action => sprintf('<a href="' . admin_url("admin-post.php") . '?action=%s&item_id=%s&action_name=%s">' . __(ucfirst($action), 'more-sql-connection') . '</a>', 'toggle_status', $item['id'], $action),
            'edit' => sprintf('<a href="?page=%s&action=%s&item_id=%s">' . __('Edit', 'more-sql-connection') . '</a>', $_REQUEST['page'], 'edit', $item['id']),
            'delete' => sprintf('<a href="' . admin_url("admin-post.php") . '?action=%s&item_id=%s">' . __('Delete', 'more-sql-connection') . '</a>', 'delete_connection', $item['id']),
        );

        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions));
    }

    function column_status($item)
    {
        $action = 'deactive';
        if ($item['status'] == 1) {
            $action = 'active';
        }

        return ucfirst($action);
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="item_ids[]" value="%s" />',
            $item['id']
        );
    }

    protected function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
            'host' => array('host', true),
            'drive' => array('drive', true)
        );
        return $sortable_columns;
    }


    public function process_bulk_action()
    {

        // security check!
        if (isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce'])) {

            $nonce = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);
            $action = 'bulk-' . $this->_args['plural'];

            if (!wp_verify_nonce($nonce, $action))
                wp_die('Nope! Security check failed!');

        }

        $action = $this->current_action();

        switch ($action) {

            case 'delete_all': {
                    if (isset($_POST['item_ids']) && count($_POST['item_ids']) > 0) {
                        $this->more_sql_connection->delete_connection($_POST['item_ids']);
                    }
                    var_dump($_POST['item_id']);
                    wp_die('Delete something');

                }
                break;

            default:
                break;
        }

        return;
    }

}
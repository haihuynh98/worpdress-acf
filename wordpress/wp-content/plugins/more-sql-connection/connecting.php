<?php
require_once(MORE_SQL_DIR . '/connection.php');
$host = $connect_name = $drive = $port = $db_user = $db_pass = $db_name = '';
$more_sql_connection = new More_Sql_Connection();
$connection_row = new Connection();
$is_edit = false;

if (isset($_GET['item_id']) && $_GET['action'] == 'edit') {
    $connection_id = $_GET['item_id'];
    $connection_row = $more_sql_connection->get_row_id($connection_id);
    $is_edit = true;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">More SQL Connection</h1>
    <?php
    if ($is_edit) {
        printf('<a href="%1$s" class="page-title-action">Add New</a>', admin_url('admin.php?page=' . $more_sql_connection->connecting_slug));
    }
    ?>
    <div class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <div class="form-wrap">
                    <?php
                    if ($is_edit) {
                        echo '<h2>Edit connection</h2>';
                    } else {
                        echo '<h2>Add connection</h2>';
                    }
                    ?>

                    <form name="add_connection" id="add_connection" method="post" enctype="multipart/form-data"
                        action="<?= admin_url('admin-post.php') ?>">
                        <?php
                        wp_nonce_field('add_connect', '_wpnonce_add_connect');
                        wp_referer_field(true);
                        if ($is_edit) {
                            echo '<input type="hidden" name="id" value="' . $connection_row->id . '">';
                            echo '<input type="hidden" name="action" value="update_connection">';
                        } else {
                            echo '<input type="hidden" name="action" value="add_connection">';
                        }
                        more_sql_render_text_field('connection_name', 'Connection name', $connection_row->name, '', true);
                        ?>

                        <div class="form-field sql_drive-wrap">
                            <label for="sql_drive">SQL drive</label>
                            <select name="sql_drive" id="sql_drive" class="postform">
                                <?php
                                $list_drive = ['mysql' => 'Mysql', 'pgsql' => 'PostgreSQL'];
                                foreach ($list_drive as $key => $drive)
                                    printf('<option value="%1$s" %3$s>%2$s</option>', $key, $drive, $key == $connection_row->drive ? 'selected' : '');
                                ?>
                            </select>
                        </div>

                        <?php
                        more_sql_render_text_field('host_name', 'Host name/IP', $connection_row->host, '', true);
                        ?>

                        <div>
                            <label for="port">Port</label>
                            <input name="port" type="text" id="port" class="small-text"
                                value="<?= $connection_row->port ?>" placeholder="3306">
                        </div>
                        <?php
                        more_sql_render_text_field('db_user', 'DB username', $connection_row->db_username, '', true);
                        more_sql_render_password_field('db_pass', 'DB password', $connection_row->db_password, '', true);
                        more_sql_render_text_field('db_name', 'DB name', $connection_row->db_name, '', true);
                        ?>
                        <div class="btn-group form-field" style>
                            <button id="test_connection" class="button button-primary" type="button" style="display: inline">Test connection</button>
                            <p class="message_connection" style="display: inline"></p>
                        </div>

                        <?php
                        if ($is_edit) {
                            submit_button(__('Save', 'more-sql-connection'), 'primary', 'save', true, 'disabled');
                        } else {
                            submit_button(__('Add new', 'more-sql-connection'), 'primary', 'add_connection', true, 'disabled');
                        }

                        ?>
                    </form>
                </div>
            </div>
        </div>
        <div id="col-right">
            <div class="col-wrap">
                <?php require_once('components/list-table.php') ?>
            </div>
        </div>
    </div>
</div>
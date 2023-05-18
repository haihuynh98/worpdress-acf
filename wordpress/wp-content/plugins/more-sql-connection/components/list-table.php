<?php
$plugin_dir = MORE_SQL_DIR;

require_once("{$plugin_dir}/connection-table.php");

$connection_table = new Connection_Tablet();

?>

<form id="connection_list_table" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
    <?php

    $connection_table->prepare_items();
    $connection_table->search_box('search', 'search_id');
    $connection_table->display();
    ?>
</form>
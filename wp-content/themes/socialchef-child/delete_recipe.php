<?php
require_once('../../../wp-load.php');

$delete = $_GET['id'];
if ($delete) {
    wp_delete_post($delete);
}
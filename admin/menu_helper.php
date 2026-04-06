<?php

function getMenuTree($parent_id, $db) {
    $menu = [];

    $sql = "SELECT * FROM menu_master 
            WHERE parent_id = '$parent_id' AND is_active = 1 
            ORDER BY sort_order ASC";

    $res = mysqli_query($db, $sql);

    while ($row = mysqli_fetch_assoc($res)) {
        $row['children'] = getMenuTree($row['id'], $db);  
        $menu[] = $row;
    }
    return $menu;
}

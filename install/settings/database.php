<?php
/* settings/database.php */

return array(
    'mysql' => array(
        'dbdriver' => 'mysql',
        'username' => 'root',
        'password' => '',
        'dbname' => 'eleave',
        'prefix' => 'eleave',
    ),
    'tables' => array(
        'category' => 'category',
        'language' => 'language',
        'leave' => 'leave',
        'leave_items' => 'leave_items',
        'logs' => 'logs',
        'user' => 'user',
        'user_meta' => 'user_meta'
    )
);

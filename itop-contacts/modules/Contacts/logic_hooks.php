<?php

$hook_array['after_save'][] = [
    1,
    'Link the matching iTop person',
    'custom/modules/Contacts/ItopContactLink.php',
    'ItopContactLink',
    'afterSave',
];

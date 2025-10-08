<?php
$defaults = ['127.0.0.1/8','10.0.0.0/8','172.16.0.0/12','192.168.0.0/16'];
$extra = array_values(array_filter(array_map('trim', explode(',', (string) env('ADMIN_ALLOWED_IPS', '')))));
return ['allowed' => array_values(array_unique(array_merge($defaults, $extra)))];

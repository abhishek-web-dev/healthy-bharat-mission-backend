<?php
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer 123456';
if (function_exists('getallheaders')) {
    var_dump(getallheaders());
}

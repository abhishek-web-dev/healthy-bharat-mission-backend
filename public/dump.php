<?php
echo json_encode(['headers' => function_exists('getallheaders') ? getallheaders() : [], 'server' => $_SERVER]);

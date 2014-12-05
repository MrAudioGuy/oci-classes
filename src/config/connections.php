<?php

namespace Libraries\Database;

return [
    'oracle-sid'        =>  [
        'driver'        =>  'pdo-via-oci8',
        'host'          =>  '172.25.200.12',
        'port'          =>  '1521',
        'database'      =>  'devhq',
        'service_name'  =>  'devhq',
        'username'      =>  'ussd',
        'password'      =>  '12345678',
        'charset'       =>  'utf8',
        'prefix'        =>  '',
    ],
    'oracle-report'     =>  [
        'driver'        =>  'pdo-via-oci8',
        'host'          =>  '172.31.21.39',
        'port'          =>  '1521',
        'database'      =>  '',
        'service_name'  =>  'ussddg',
        'username'      =>  'dev_panel',
        'password'      =>  '4NjEfUTs86ghdZPj',
        'charset'       =>  'utf8',
        'prefix'        =>  '',
    ],
	'oracle-live'     =>  [
		'driver'        =>  'pdo-via-oci8',
		'host'          =>  '172.31.21.38',
		'port'          =>  '1521',
		'database'      =>  '',
		'service_name'  =>  'ussd',
		'username'      =>  'dev_panel',
		'password'      =>  '4NjEfUTs86ghdZPj',
		'charset'       =>  'utf8',
		'prefix'        =>  '',
	],
];
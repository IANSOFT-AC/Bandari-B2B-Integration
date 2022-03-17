<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
	'NavisionUsername'=> env('NAV_USER'),
    'NavisionPassword'=> env('NAV_PASSWORD'),


    'server'=> env('NAV_SERVER'),
    'WebServicePort'=> env('NAV_PORT'),
    'ServerInstance'=> env('NAV_INSTANCE'),
    'CompanyName'=> env('NAV_COMPANY'),
    'DBCompanyName' => env('NAV_DB_COMPANY'),


    'ldPrefix'=>'francis',//ACTIVE DIRECTORY prefix
    'adServer' => 'DC2SVR.AASCIENCES.AC.KE', //Active directory domain co
	
	'codeUnits' => [
        'CoopB2B'
	],
	'ServiceName' => [
		'CoopB2B' => 'CoopB2B',		
    ],

    'AuthMode' => [
        'AD' => True,
        'Basic' => False 
    ]
];

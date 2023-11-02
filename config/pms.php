<?php

// config('pms.lyMeta.fields.text')
return [
	'lyMeta'=>[
		'extraFields' =>  [
			'text' => [
		            [
		                'field' => 'name_en',
		                'field_desc' => 'Program English Title',
		                'placeholder' => '节目英文标题',
		            ],
		            [
		                'field' => 'supervisor',
		                'field_desc' => 'Supervisor',
		                'placeholder' => '节目监制',
		            ],
		            [
		                'field' => 'program_column',
		                'field_desc' => 'Program Column',
		                'placeholder' => '节目栏目',
		            ],
		            [
		                'field' => 'program_email',
		                'field_desc' => 'Program E-mail',
		                'placeholder' => '节目电邮',
		            ],
		            [
		                'field' => 'program_sms',
		                'field_desc' => 'Program SMS Number',
		                'placeholder' => '节目短信号码',
		            ],
		            [
		                'field' => 'program_sms_keyword',
		                'field_desc' => 'Program Abbreviation For SMS',
		                'placeholder' => '节目短信用简写',
		            ],
		            [
		                'field' => 'program_phone_number',
		                'field_desc' => 'Program Phone Number',
		                'placeholder' => '节目电话号码',
		            ],
		            [
		                'field' => 'program_phone_time',
		                'field_desc' => 'Time To Receive Phone Call',
		                'placeholder' => '接听电话时间',
		            ],
			],
		],
	],
	'ltsMeta'=>[
		'extraFields' =>  [
			'text' => [
	            [
	                'field' => 'name_en',
	                'field_desc' => 'LTS Program English Title',
	                'placeholder' => '节目英文标题',
	            ],
			],
		],
	],
];
<?php

return [
	'extensions' => [
		'im:chat/selector/adapter/dialog-list',
		'im:messenger/core',
		'im:messenger/lib/element',
		'type',
		'im:messenger/lib/utils',
	],
	'bundle' => [
		'./src/base',
		'./src/user',
		'./src/adapter/user',
	],
];
<?php

return [
	'extensions' => [
		'type',
		'im:messenger/lib/logger',
		'im:messenger/db/repository',
		'im:messenger/db/table',
	],
	'bundle' => [
		'./src/db-updater',
		'./src/version',
		'./src/update',
		'./src/version/1',
		'./src/version/2',
		'./src/version/3',
		'./src/version/4',
	],
];
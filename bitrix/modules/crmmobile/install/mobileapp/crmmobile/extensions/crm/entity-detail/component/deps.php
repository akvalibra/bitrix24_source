<?php

return [
	'extensions' => [
		'alert',
		'haptics',
		'rest',
		'loc',
		'layout/ui/detail-card/tabs/factory/type',
		'native/contacts',
		'notify',
		'notify-manager',
		'utils/prop',
		'utils/object',
		'utils/string',
		'utils/function',
		'utils/type',
		'event-emitter',
		'pull/client/events',

		'crm:assets/entity',
		'crm:category-list-view',
		'crm:storage/category',
		'crm:loc',
		'crm:required-fields',
		'crm:timeline/scheduler',
		'crm:type',

		'crm:entity-detail/component/ajax-error-handler',
		'crm:entity-detail/component/header-processor',
		'crm:entity-detail/component/menu-provider',
		'crm:entity-detail/component/additional-button-provider',
	],
	'bundle' => [
		'./ajax-error-handler',
		'./on-close-handler',
		'./header-processor',
		'./menu-provider',
		'./right-buttons-provider',
		'./set-available-tabs',
		'./timeline-push-processor',
		'./global-events',
		'./custom-events',
		'./smart-activity-menu-item',
		'./on-model-ready',
	],
];
/**
 * @module crm/timeline/action/redirect
 */
jn.define('crm/timeline/action/redirect', (require, exports, module) => {

	const { BaseTimelineAction } = require('crm/timeline/action/base');
	const { inAppUrl } = require('in-app-url');
	const { Loc } = require('loc');

	class RedirectAction extends BaseTimelineAction
	{
		execute()
		{
			inAppUrl.open(this.value, this.actionParams, (url) => {

				qrauth.open({
					title: Loc.getMessage('CRM_TIMELINE_DESKTOP_VERSION'),
					redirectUrl: url.toString(),
				});

			});
		}
	}

	module.exports = { RedirectAction };

});
/**
 * @module crm/in-app-url/routes
 */
jn.define('crm/in-app-url/routes', (require, exports, module) => {

	const { EntityDetailOpener } = require('crm/entity-detail/opener');
	const { TypeId, TypeName } = require('crm/type');
	const { ProfileView } = require('user/profile');

	const openCrmEntity = (entityTypeId, entityId, { linkText = '', canOpenInDefault } = {}) => {
		EntityDetailOpener.open(
			{ entityId, entityTypeId },
			{ titleParams: { text: linkText } },
			null,
			canOpenInDefault,
		);
	};

	const openCrm = ({ activeTab }) => {
		const componentParams = {};

		if (activeTab === TypeName.Company || activeTab === TypeName.Contact)
		{
			componentParams.activeTabName = activeTab;
		}

		ComponentHelper.openLayout(
			{
				widgetParams: {
					titleParams: {
						text: 'CRM',
					},
				},
				name: 'crm:crm.tabs',
				canOpenInDefault: true,
				componentParams,
			},
		);
	};

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = function(inAppUrl) {
		const extensionData = jnExtensionData.get('crm:in-app-url/routes');

		if (typeof extensionData === 'undefined' || !extensionData.isUniversalActivityScenarioEnabled)
		{
			return;
		}

		inAppUrl.register('/crm/deal/details/:id/', ({ id }, { context }) => {

			openCrmEntity(TypeId.Deal, id, context);

		}).name('crm:deal');

		inAppUrl.register('/crm/deal/', () => {
			openCrm({
				activeTab: TypeName.Deal,
			});
		}).name('crm:dealList');

		inAppUrl.register('/crm/contact/details/:id/', ({ id }, { context }) => {

			openCrmEntity(TypeId.Contact, id, context);

		}).name('crm:contact');

		inAppUrl.register('/crm/contact/list/', () => {
			openCrm({
				activeTab: TypeName.Contact,
			});
		}).name('crm:contactList');

		inAppUrl.register('/crm/contact/', () => {
			openCrm({
				activeTab: TypeName.Contact,
			});
		}).name('crm:contactList');

		inAppUrl.register('/crm/company/details/:id/', ({ id }, { context }) => {

			openCrmEntity(TypeId.Company, id, context);

		}).name('crm:company');

		inAppUrl.register('/crm/company/list/', () => {
			openCrm({
				activeTab: TypeName.Company,
			});
		}).name('crm:companyList');

		inAppUrl.register('/crm/company/', () => {
			openCrm({
				activeTab: TypeName.Company,
			});
		}).name('crm:companyList');

		inAppUrl.register('/company/personal/user/:userId/', ({ userId }, { context }) => {

			const widgetParams = { groupStyle: true };
			const isBackdrop = Boolean(context.backdrop);

			if (isBackdrop)
			{
				widgetParams.backdrop = {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				};
			}

			PageManager.openWidget('list', widgetParams)
				.then(list => ProfileView.open({ userId, isBackdrop }, list));

		}).name('crm:user');

	};

});
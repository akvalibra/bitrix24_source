/**
 * @module crm/timeline/scheduler/providers/sms
 */
jn.define('crm/timeline/scheduler/providers/sms', (require, exports, module) => {

	const { Loc } = require('loc');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');

	/**
	 * @class TimelineSchedulerSmsProvider
	 */
	class TimelineSchedulerSmsProvider extends TimelineSchedulerBaseProvider
	{
		static getId()
		{
			return 'sms';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_MENU_TITLE');
		}

		static getMenuIcon()
		{
			return `<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20.3089 12.8163H8.43606C8.20142 12.8163 8.01371 12.6608 8.01371 12.4665V11.0283C8.01371 10.8339 8.20142 10.6784 8.43606 10.6784H20.3089C20.5435 10.6784 20.7312 10.8339 20.7312 11.0283V12.4665C20.7781 12.6608 20.5435 12.8163 20.3089 12.8163ZM20.3089 17.1027H8.43606C8.20142 17.1027 8.01371 16.9472 8.01371 16.7528V15.3146C8.01371 15.1203 8.20142 14.9648 8.43606 14.9648H20.3089C20.5435 14.9648 20.7312 15.1203 20.7312 15.3146V16.7528C20.7781 16.9472 20.5435 17.1027 20.3089 17.1027ZM22.3345 6.75H6.37232C5.33532 6.75 4.47116 7.52743 4.47116 8.46034V19.7064C4.47116 20.6393 5.33532 21.4167 6.37232 21.4167H17.6004C17.73 21.4167 17.8164 21.4556 17.9028 21.5333L20.5817 24.1377C20.841 24.3709 21.2731 24.2154 21.2731 23.9045V21.7666C21.2731 21.5722 21.4459 21.4167 21.6619 21.4167H22.3777C23.4147 21.4167 24.2789 20.6393 24.2789 19.7064V8.46034C24.2356 7.52743 23.3715 6.75 22.3345 6.75Z" fill="#828B95"/></svg>`;
		}

		static getMenuPosition()
		{
			return 200;
		}

		static isSupported()
		{
			return false;
		}
	}

	module.exports = { TimelineSchedulerSmsProvider };

});
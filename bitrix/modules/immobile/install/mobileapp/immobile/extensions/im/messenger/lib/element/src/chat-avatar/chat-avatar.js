/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/element/chat-avatar
 */
jn.define('im/messenger/lib/element/chat-avatar', (require, exports, module) => {

	const { DialogHelper } = jn.require('im/messenger/lib/helper');
	const { MessengerParams } = jn.require('im/messenger/lib/params');

	/**
	 * @class ChatAvatar
	 */
	class ChatAvatar
	{
		static createFromDialogId(dialogId, options = {})
		{
			return new this(dialogId, options);
		}

		constructor(dialogId, options = {})
		{
			this.avatar = null;
			this.color = null;

			if (DialogHelper.isDialogId(dialogId))
			{
				this.createDialogAvatar(dialogId);
			}
			else
			{
				this.createUserAvatar(dialogId);
			}
		}

		static getImagePath()
		{
			return '/bitrix/mobileapp/immobile/extensions/im/messenger/lib/element/src/chat-avatar/images/';
		}

		createDialogAvatar(dialogId)
		{
			const dialog = MessengerStore.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return;
			}

			if (dialog.chatId === MessengerParams.getGeneralChatId())
			{
				this.avatar = ChatAvatar.getImagePath() + 'avatar_general.png';
				return;
			}

			if (dialog.entityType === 'SUPPORT24_QUESTION')
			{
				this.avatar = ChatAvatar.getImagePath() + 'avatar_support_24.png';
				return;
			}

			this.avatar = dialog.avatar;
			if (this.avatar === '')
			{
				this.color = dialog.color;
			}
		}

		createUserAvatar(userId)
		{
			const user = MessengerStore.getters['usersModel/getUserById'](userId);
			if (!user)
			{
				return;
			}

			this.avatar = user.avatar;
			if (this.avatar === '')
			{
				this.color = user.color;
			}
		}

		getTitleParams()
		{
			const titleParams = {
				useLetterImage: true,
			};

			if (this.avatar)
			{
				titleParams.imageUrl = this.avatar;
			}

			if (this.color)
			{
				titleParams.imageColor = this.color;
			}

			return titleParams;
		}
	}

	module.exports = {
		ChatAvatar,
	};
});
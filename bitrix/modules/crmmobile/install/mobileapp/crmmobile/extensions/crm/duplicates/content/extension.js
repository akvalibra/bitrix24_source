/**
 * @module crm/duplicates/content
 */
jn.define('crm/duplicates/content', (require, exports, module) => {

	const { DuplicatesPanel } = require('crm/duplicates/panel');
	const { EventEmitter } = require('event-emitter');
	const { Loc } = require('loc');

	/**
	 * @class DuplicatesContent
	 */

	class DuplicatesContent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.uid = Random.getString();
			this.parentEventEmitter = EventEmitter.createWithUid(props.parentUid);
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.onUseContact = this.handleUseContact.bind(this);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('Duplicate::onUseContact', this.onUseContact);
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off('Duplicate::onUseContact', this.onUseContact);
		}

		handleUseContact(entityId, entityTypeId)
		{
			this.parentEventEmitter.emit('Duplicate::onUpdate', [{ entityTypeId, entityId }]);
			this.parentEventEmitter.emit('DetailCard::close');
		}

		openPanel(duplicates)
		{
			const { isAllowed, entityType } = this.props;
			const panel = new DuplicatesPanel({
				entityType,
				isAllowed,
				duplicates,
				onUseContact: this.onUseContact,
				uid: this.uid,
			});

			panel.open();
		}

		render()
		{
			const { duplicates, fieldType, style, color } = this.props;
			const { DUPLICATES, ENTITY_TOTAL_TEXT } = duplicates;

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Text(
					{
						style,
						text: Loc.getMessage(
							`FIELDS_PHONE_DUPLICATE_WARNING_${fieldType}`,
							{ '#ENTITY_TOTAL_TEXT#': ENTITY_TOTAL_TEXT },
						),
					},
				),
				View(
					{
						onClick: () => {
							if (Array.isArray(DUPLICATES) && DUPLICATES.length)
							{
								this.openPanel(DUPLICATES[0]);
							}
						},
					},
					Text(
						{
							style: {
								...style,
								marginLeft: 4,
								backgroundColor: color,
								color: '#F6D9A1',
							},
							text: Loc.getMessage('FIELDS_PHONE_DUPLICATE_WARNING_MORE'),
						},
					),
				),
			);
		}
	}

	module.exports = { DuplicatesContent };
});
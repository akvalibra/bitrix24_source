/**
 * @module crm/timeline/item/ui/body/blocks
 */
jn.define('crm/timeline/item/ui/body/blocks', (require, exports, module) => {

	const { TimelineItemBodyTextBlock: TextBlock } = require('crm/timeline/item/ui/body/blocks/text-block');
	const { TimelineItemBodyLinkBlock: LinkBlock } = require('crm/timeline/item/ui/body/blocks/link-block');
	const { TimelineItemBodyWithTitleBlock: WithTitle } = require('crm/timeline/item/ui/body/blocks/with-title');
	const { TimelineItemBodyValueChangeBlock: ValueChange } = require('crm/timeline/item/ui/body/blocks/value-change');
	const { TimelineItemBodyLineOfTextBlocks: LineOfTextBlocks } = require('crm/timeline/item/ui/body/blocks/line-of-text-blocks');
	const { TimelineItemBodyAudioBlock: TimelineAudio } = require('crm/timeline/item/ui/body/blocks/audio-block');
	const { TimelineItemBodyClientMark: ClientMark } = require('crm/timeline/item/ui/body/blocks/client-mark');
	const { TimelineItemBodyDatePillBlock: DatePill } = require('crm/timeline/item/ui/body/blocks/date-pill');
	const { TimelineItemBodyDateBlock: DateBlock } = require('crm/timeline/item/ui/body/blocks/date-block');
	const { TimelineItemBodyPlayerAlertBlock: PlayerAlert } = require('crm/timeline/item/ui/body/blocks/player-alert');
	const { TimelineItemBodyEditableDescriptionBlock: EditableDescription } = require('crm/timeline/item/ui/body/blocks/editable-description');
	const { TimelineItemBodyNoteBlock: Note } = require('crm/timeline/item/ui/body/blocks/note');

	const AvailableBlocks = {
		TextBlock,
		LinkBlock,
		WithTitle,
		ValueChange,
		LineOfTextBlocks,
		TimelineAudio,
		ClientMark,
		DatePill,
		DateBlock,
		PlayerAlert,
		EditableDescription,
		Note,
	};

    /**
     * @class TimelineItemBodyBlockFactory
     */
    class TimelineItemBodyBlockFactory
    {
		/**
		 * @param {TimelineItemModel} model
		 * @param {EventEmitter} itemScopeEventBus
		 * @param {function} onAction
		 */
		constructor({ model, itemScopeEventBus, onAction })
		{
			this.model = model;
			this.itemScopeEventBus = itemScopeEventBus;
			this.onAction = onAction;
		}

		/**
		 * @param {string} rendererName
		 * @param {object} props
		 * @returns {LayoutComponent|null}
		 */
        make(rendererName, props = {})
		{
			if (AvailableBlocks[rendererName])
			{
				return new AvailableBlocks[rendererName](props, this);
			}

			console.log('Content block not supported', rendererName, props);

			return null;
		}
    }

    module.exports = { TimelineItemBodyBlockFactory };

});
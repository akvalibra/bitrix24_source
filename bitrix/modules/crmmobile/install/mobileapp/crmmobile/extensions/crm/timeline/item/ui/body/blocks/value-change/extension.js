/**
 * @module crm/timeline/item/ui/body/blocks/value-change
 */
jn.define('crm/timeline/item/ui/body/blocks/value-change', (require, exports, module) => {

	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { transparent } = require('utils/color');

	/**
	 * @class TimelineItemBodyValueChangeBlock
	 */
	class TimelineItemBodyValueChangeBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
					}
				},
				this.renderValue(this.props.from),
				this.renderArrow(),
				this.renderValue(this.props.to),
			);
		}

		renderValue(value)
		{
			return View(
				{
					style: {
						borderRadius: 40,
						backgroundColor: transparent('#000000', 0.05),
						paddingTop: 4,
						paddingBottom: 4,
						paddingLeft: 8,
						paddingRight: 8,
					}
				},
				Text({
					text: value,
					style: {
						fontSize: 12,
						fontWeight: '400',
						color: '#525C69',
					}
				})
			);
		}

		renderArrow()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						marginLeft: 8,
						marginRight: 8,
					}
				},
				Image({
					svg: {
						content: `<svg width="9" height="8" viewBox="0 0 9 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.5V0.5L9 4L5.5 7.5V4.5H0V3.5H5.5Z" fill="#A9ADB3"/></svg>`,
					},
					style: {
						width: 9,
						height: 8,
					}
				})
			);
		}
	}

	module.exports = { TimelineItemBodyValueChangeBlock };

});
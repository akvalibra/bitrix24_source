/**
 * @module crm/timeline/item/ui/footer
 */
jn.define('crm/timeline/item/ui/footer', (require, exports, module) => {

	const { TimelineItemContextMenu } = require('crm/timeline/item/ui/context-menu');
	const {
		TimelineButtonType,
		TimelineButtonVisibilityFilter,
		TimelineButtonSorter
	} = require('crm/timeline/item/ui/styles');
	const { transparent } = require('utils/color');
	const { get } = require('utils/object');

	const nothing = () => {};

	const MAX_BUTTONS_COUNT = 3;
	const MAX_ICONS_COUNT = 2;

    /**
     * @class TimelineItemFooter
     */
    class TimelineItemFooter extends LayoutComponent
    {
		/**
		 * @return {TimelineItemContextMenu}
		 */
		get menu()
		{
			return new TimelineItemContextMenu({
				items: this.availableMenuItems,
				onAction: (action) => this.onAction(action),
				isReadonly: this.props.isReadonly,
			});
		}

		/**
		 * @return {*[]}
		 */
		get availableButtons()
		{
			return Object.values(this.props.buttons || {})
				.filter((button) => TimelineButtonVisibilityFilter(button, this.props.isReadonly))
				.sort(TimelineButtonSorter);
		}

		/**
		 * @return {*[]}
		 */
		get displayedButtons()
		{
			return this.availableButtons.slice(0, MAX_BUTTONS_COUNT);
		}

		/**
		 * @return {*[]}
		 */
		get hiddenButtons()
		{
			return this.availableButtons.slice(MAX_BUTTONS_COUNT);
		}

		/**
		 * @return {*[]}
		 */
		get availableIcons()
		{
			return Object.values(this.props.additionalButtons || {})
				.filter((button) => TimelineButtonVisibilityFilter(button, this.props.isReadonly))
				.sort(TimelineButtonSorter);
		}

		/**
		 * @return {*[]}
		 */
		get displayedIcons()
		{
			return this.availableIcons.slice(0, MAX_ICONS_COUNT);
		}

		/**
		 * @return {*[]}
		 */
		get availableMenuItems()
		{
			const rawItems = Object.values(get(this, 'props.menu.items', {}));

			return [ ...rawItems, ...this.hiddenButtons ];
		}

        render()
        {
			const hasButtons = this.displayedButtons.length > 0;
			const hasIconsOrMenu = this.displayedIcons.length || this.menu.hasItems();

			if (!hasButtons && !hasIconsOrMenu)
			{
				return null;
			}

            return View(
				{
					style: {
						paddingHorizontal: 16,
						paddingTop: 0,
						paddingBottom: 12,
						flexDirection: 'row',
						flexWrap: 'wrap',
					}
				},
				...this.displayedButtons.map((button, index) => this.renderButton(button, index)),
				hasIconsOrMenu && View(
					{
						style: {
							width: this.displayedButtons.length % 2 === 0 ? '100%' : '50%',
							flexDirection: 'row',
							justifyContent: 'flex-end',
							marginBottom: 12,
						}
					},
					...this.displayedIcons.map(icon => this.renderIcon(icon)),
					this.renderMenu(),
				)
			);
        }

		renderButton(button, index)
		{
			return View(
				{
					style: {
						width: '50%',
						marginBottom: 12,
						paddingRight: index % 2 === 0 ? 6 : 0,
						paddingLeft: index % 2 === 1 ? 6 : 0,
					}
				},
				TimelineItemButton({
					...button,
					onClick: () => this.onAction(button.action),
				}),
			);
		}

		renderIcon(icon)
		{
			const code = icon.iconName;

			if (!Icons[code])
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						paddingLeft: 12,
						paddingRight: 12,
						paddingTop: 5,
						paddingBottom: 5,
					},
					onClick: () => this.onAction(icon.action),
				},
				Image({
					style: {
						width: 28,
						height: 28,
					},
					svg: {
						content: typeof Icons[code] === 'function' ? Icons[code](icon, this) : Icons[code],
					}
				})
			);
		}

		renderMenu()
		{
			if (!this.menu.hasItems())
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						paddingLeft: 8,
						paddingRight: 4,
						paddingTop: 16,
						paddingBottom: 16,
					},
					onClick: () => this.menu.open(),
				},
				Image({
					style: {
						width: 20,
						height: 6,
					},
					svg: {
						content: `<svg width="20" height="6" viewBox="0 0 20 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.99935 5.33317C4.28801 5.33317 5.33268 4.2885 5.33268 2.99984C5.33268 1.71117 4.28801 0.666504 2.99935 0.666504C1.71068 0.666504 0.666016 1.71117 0.666016 2.99984C0.666016 4.2885 1.71068 5.33317 2.99935 5.33317Z" fill="#BDC1C6"/><path d="M9.99935 5.33317C11.288 5.33317 12.3327 4.2885 12.3327 2.99984C12.3327 1.71117 11.288 0.666504 9.99935 0.666504C8.71068 0.666504 7.66601 1.71117 7.66601 2.99984C7.66601 4.2885 8.71068 5.33317 9.99935 5.33317Z" fill="#BDC1C6"/><path d="M19.3327 2.99984C19.3327 4.2885 18.288 5.33317 16.9993 5.33317C15.7107 5.33317 14.666 4.2885 14.666 2.99984C14.666 1.71117 15.7107 0.666504 16.9993 0.666504C18.288 0.666504 19.3327 1.71117 19.3327 2.99984Z" fill="#BDC1C6"/></svg>`,
					}
				})
			);
		}

		onAction(action)
		{
			if (action && this.props.onAction)
			{
				this.props.onAction(action);
			}
		}
    }

	function TimelineItemButton({ type, title, onClick = nothing })
	{
		return View(
			{
				onClick,
				style: {
					backgroundColor: type === TimelineButtonType.PRIMARY ? '#00A2E8' : transparent('#ffffff'),
					borderRadius: 512,
					borderWidth: 1,
					borderColor: type === TimelineButtonType.PRIMARY ? '#00A2E8' : '#828B95',
					paddingHorizontal: 16,
					paddingVertical: 10,
					flexDirection: 'row',
					justifyContent: 'center',
				},
			},
			Text({
				text: title,
				ellipsize: 'end',
				numberOfLines: 1,
				style: {
					color: type === TimelineButtonType.PRIMARY ? '#ffffff' : '#333333',
					fontSize: 15,
					fontWeight: '500',
				}
			})
		);
	}

	const IconColors = {
		primary: '#2FC6F6',
		default: '#BDC1C6',
	};

	const Icons = {
		script: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.5936 10.7289C10.2714 10.7289 10.0102 10.9901 10.0102 11.3123V12.1735C10.0102 12.4956 10.2714 12.7568 10.5936 12.7568H16.5031C16.8252 12.7568 17.0864 12.4956 17.0864 12.1735V11.3123C17.0864 10.9901 16.8252 10.7289 16.5031 10.7289H10.5936Z" fill="#BDC1C6"/><path d="M10.0102 15.2022C10.0102 14.88 10.2714 14.6188 10.5936 14.6188H16.5031C16.8252 14.6188 17.0864 14.88 17.0864 15.2022V16.0633C17.0864 16.3855 16.8252 16.6467 16.5031 16.6467H10.5936C10.2714 16.6467 10.0102 16.3855 10.0102 16.0633V15.2022Z" fill="#BDC1C6"/><path fill-rule="evenodd" clip-rule="evenodd" d="M5.83325 12.25C5.83325 8.70621 8.70609 5.83333 12.2499 5.83333H21.6495C21.8019 5.83333 21.9441 5.86099 22.0731 5.91063C22.636 6.01969 23.3622 6.29573 23.9882 6.95656C24.7222 7.73129 25.0406 8.72797 25.1802 9.34005C25.3974 10.2929 24.6235 11.0297 23.8006 11.0297L20.9999 11.0297V18.0833C20.9999 20.3385 19.1717 22.1667 16.9166 22.1667H6.41659C6.09442 22.1667 5.83325 21.9059 5.83325 21.5837V12.25ZM21.4596 9.2797L23.3479 9.27969C23.2161 8.88784 23.0151 8.47386 22.7179 8.16015C22.6252 8.06239 22.5285 7.9812 22.4305 7.91382L21.5432 9.15607C21.5142 9.19664 21.4863 9.23786 21.4596 9.2797ZM7.82686 12.4934C7.82686 9.91611 9.91619 7.82693 12.4935 7.82693H20.166C20.166 7.82693 19.0339 9.07451 19.0339 10.5V17.4898C19.0339 18.9717 17.8325 20.1731 16.3506 20.1731H7.82686V12.4934Z" fill="#BDC1C6"/></svg>`,
		note: (icon) => {
			const color = IconColors[icon.color] || IconColors.default;
			return `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 5C6.11929 5 5 6.11929 5 7.5V16.5C5 17.8807 6.11929 19 7.5 19H14.5222C15.16 19 15.7736 18.7562 16.2376 18.3186L18.2154 16.4531C18.7162 15.9808 19 15.3229 19 14.6345V7.5C19 6.11929 17.8807 5 16.5 5H7.5ZM7.7088 6.7088C7.15652 6.7088 6.7088 7.15652 6.7088 7.7088V16.2912C6.7088 16.8435 7.15652 17.2912 7.7088 17.2912H14V14.5C14 14.2239 14.2239 14 14.5 14H17.2912V7.7088C17.2912 7.15652 16.8435 6.7088 16.2912 6.7088H7.7088ZM9.08035 9C8.8042 9 8.58035 9.22386 8.58035 9.5V10.2381C8.58035 10.5143 8.8042 10.7381 9.08035 10.7381H14.1456C14.4218 10.7381 14.6456 10.5143 14.6456 10.2381V9.5C14.6456 9.22386 14.4218 9 14.1456 9H9.08035Z" fill="${color}"/></svg>`
		},
	};

    module.exports = { TimelineItemFooter };

});
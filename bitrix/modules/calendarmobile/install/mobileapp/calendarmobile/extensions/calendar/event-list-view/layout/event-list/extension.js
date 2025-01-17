/**
 * @module calendar/event-list-view/layout/event-list
 */
jn.define('calendar/event-list-view/layout/event-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { DateHelper } = require('calendar/date-helper');
	const { DayLabel } = require('calendar/event-list-view/layout/day-label');
	const { Event } = require('calendar/event-list-view/layout/event');
	const { clone } = require('utils/object');
	const { Type } = require('type');
	const { Loc } = require('loc');

	const EventListItemType = {
		TYPE_EVENT: 'event',
		TYPE_DAY_LABEL: 'day-label',
		TYPE_EMPTY_SPACE: 'empty-space',
	};

	/**
	 * @class EventListComponent
	 */
	class EventListComponent extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.eventManager = props.eventManager;

			this.state = {
				refreshing: true,
				events: [],
				search: false,
				invitations: false,
				searchByPreset: false,
			};

			this.listRef = null;
		}

		get isSearchMode()
		{
			return this.props.isSearchMode;
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				this.isFilledList() ? this.renderList() : this.renderEmptyState(),
			);
		}

		renderList()
		{
			const items = this.prepareItemsForList(this.state.events);

			return ListView({
				ref: (ref) => {
					this.listRef = ref;
				},
				data: [{ items }],
				style: {
					flexDirection: 'column',
					flexGrow: 1,
				},
				onScrollBeginDrag: () => this.props.onScroll(),
				renderItem: (props) => {
					return View(
						{},
						this.renderItemContent(props),
					);
				},
			});
		}

		renderItemContent(props)
		{
			switch (props.type)
			{
				case EventListItemType.TYPE_DAY_LABEL:
					return DayLabel(props);
				case EventListItemType.TYPE_EVENT:
					return this.renderEvent(props);
				case EventListItemType.TYPE_EMPTY_SPACE:
					return this.renderEmptySpace();
				default:
					return null;
			}
		}

		renderEvent(props)
		{
			const event = this.eventManager.getByUniqueId(props.eventUniqueId);

			if (!event)
			{
				return null;
			}

			return Event({
				event,
				isSearch: this.isSearchMode,
				dayCode: props.dayCode,
				isLongWithTime: props.isLongWithTime,
				isFullDay: props.isFullDay,
				isUntil: props.isUntil,
				onClick: this.props.onItemSelected,
			});
		}

		renderEmptySpace()
		{
			return View(
				{
					style: {
						marginTop: 12,
						marginBottom: 13,
					},
				},
				Text(
					{
						text: ' ',
					},
				),
			);
		}

		renderEmptyState()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						flex: 1,
						paddingHorizontal: 20,
						marginBottom: 15,
						opacity: 0,
					},
					onClick: () => {
						this.props.onEmptyStateClick();
					},
					ref: ref => ref?.animate({ duration: 200, opacity: 1, delay: 500 }),
					testId: `calendar_event_list_empty_state`,
				},
				!this.state.search && !this.state.searchByPreset && this.renderEmptyEventsState(),
				this.state.search && this.renderEmptySearchState(),
				this.state.searchByPreset && this.renderEmptyPresetState(),
			);
		}

		renderEmptyEventsState()
		{
			return View(
				{},
				Text(
					{
						text: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_TITLE'),
						style: {
							fontSize: 20,
							fontWeight: '600',
						},
						testId: `calendar_event_list_empty_state_events`,
					}
				),
			);
		}

		renderEmptySearchState()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
					},
					testId: `calendar_event_list_empty_state_search`,
				},
				Text({
					style: {
						fontSize: 20,
						fontWeight: '400',
						color: AppTheme.colors.base2,
						textAlign: 'center',
					},
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TITLE'),
				}),
				Text({
					style: {
						fontSize: 16,
						fontWeight: '400',
						marginTop: 20,
						color: AppTheme.colors.base2,
						textAlign: 'center',
					},
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TEXT'),
				}),
			);
		}

		renderEmptyPresetState()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
					},
					testId: `calendar_event_list_empty_state_preset`,
				},
				Image({
					style: {
						width: 158,
						height: 129,
					},
					svg: {
						content: icons.calendarEmpty,
					},
				}),
				Text({
					style: {
						fontSize: 18,
						fontWeight: '400',
						marginTop: 20,
						color: AppTheme.colors.base2,
						textAlign: 'center',
					},
					text: this.state.invitations
						? Loc.getMessage('M_CALENDAR_EVENT_LIST_NO_INVITATIONS_TITLE')
						: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TITLE')
					,
				}),
			);
		}

		setFilterResult(events, searchByPreset, invitations)
		{
			// eslint-disable-next-line no-param-reassign
			events = this.prepareItemsForList(events, false);

			this.setState({
				events,
				refreshing: false,
				search: !searchByPreset,
				searchByPreset,
				invitations,
			}, () => {
				this.listRef?.scrollToBegin(true);
			});
		}


		getEventsForDay(day)
		{
			const dayCode = DateHelper.getDayCode(day);

			const events = this.prepareItemsForList(this.eventManager.getEventsByDay(dayCode));

			this.setState({
				events,
				refreshing: false,
				search: false,
				invitations: false,
			}, () => {
				this.listRef?.scrollToBegin(true);
			});
		}

		prepareItemsForList(items, addEmpty = true)
		{
			const result = clone(items);

			if (addEmpty && Type.isArrayFilled(result))
			{
				result.push({
					type: EventListItemType.TYPE_EMPTY_SPACE,
					// eslint-disable-next-line no-undef
					key: Random.getString(3),
				});
			}

			return result.map((item) => {
				return {
					...item,
					// eslint-disable-next-line no-undef
					key: Random.getString(3),
				};
			});
		}

		isFilledList()
		{
			const { events } = this.state;

			return Type.isArrayFilled(events);
		}
	}

	const icons = {
		calendarEmpty: '<svg width="218" height="178" viewBox="0 0 218 178" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M156.909 5.08173C158.238 5.08173 159.315 4.0047 159.315 2.67612C159.315 1.34754 158.238 0.270508 156.909 0.270508C155.58 0.270508 154.503 1.34754 154.503 2.67612C154.503 4.0047 155.58 5.08173 156.909 5.08173ZM38.2039 25.8624C41.0841 25.8624 43.4189 23.5275 43.4189 20.6474C43.4189 17.7672 41.0841 15.4324 38.2039 15.4324C35.3237 15.4324 32.9889 17.7672 32.9889 20.6474C32.9889 23.5275 35.3237 25.8624 38.2039 25.8624ZM38.2039 22.9547C39.4782 22.9547 40.5112 21.9217 40.5112 20.6474C40.5112 19.3731 39.4782 18.34 38.2039 18.34C36.9296 18.34 35.8965 19.3731 35.8965 20.6474C35.8965 21.9217 36.9296 22.9547 38.2039 22.9547ZM11.7102 76.7733C15.5894 76.7733 18.7341 73.6286 18.7341 69.7494C18.7341 65.8702 15.5894 62.7255 11.7102 62.7255C7.83099 62.7255 4.68628 65.8702 4.68628 69.7494C4.68628 73.6286 7.83099 76.7733 11.7102 76.7733ZM11.7102 73.1075C13.5648 73.1075 15.0683 71.604 15.0683 69.7494C15.0683 67.8947 13.5648 66.3912 11.7102 66.3912C9.85555 66.3912 8.35206 67.8947 8.35206 69.7494C8.35206 71.604 9.85555 73.1075 11.7102 73.1075ZM190.039 165.155C192.801 165.155 195.04 162.916 195.04 160.154C195.04 157.393 192.801 155.154 190.039 155.154C187.278 155.154 185.039 157.393 185.039 160.154C185.039 162.916 187.278 165.155 190.039 165.155ZM190.039 162.553C191.364 162.553 192.438 161.479 192.438 160.154C192.438 158.83 191.364 157.756 190.039 157.756C188.715 157.756 187.641 158.83 187.641 160.154C187.641 161.479 188.715 162.553 190.039 162.553ZM110 178C157.496 178 196 139.496 196 91.9998C196 44.5033 157.496 5.99976 110 5.99976C62.5035 5.99976 24 44.5033 24 91.9998C24 139.496 62.5035 178 110 178ZM23.9588 171.988H7.24022C7.09716 171.988 6.95534 171.983 6.81493 171.972C3.03648 171.884 0.000190901 168.709 0 164.805C0.00109851 162.902 0.737676 161.077 2.04769 159.733C2.71919 159.043 3.51317 158.509 4.37699 158.152C4.36254 157.958 4.35518 157.763 4.35518 157.565C4.35637 155.558 5.13359 153.633 6.51587 152.214C7.89815 150.795 9.77226 149.999 11.7259 150C14.2285 150.003 16.438 151.289 17.7658 153.252C18.3964 153.022 19.0752 152.898 19.7823 152.898C22.9062 152.902 25.472 155.349 25.7694 158.479C28.7627 159.149 31.0026 161.89 31 165.166C30.9969 168.942 28.0168 172.001 24.3426 172C24.2138 172 24.0858 171.996 23.9588 171.988ZM201.138 38.992H213.003C213.093 38.9971 213.184 38.9997 213.275 38.9998C215.883 39.0008 217.998 36.9152 218 34.3407C218.002 32.1067 216.412 30.2382 214.288 29.7809C214.077 27.6471 212.256 25.9785 210.039 25.9758C209.537 25.9756 209.055 26.0606 208.608 26.2172C207.666 24.8788 206.098 24.0019 204.322 23.9998C202.935 23.9989 201.605 24.5419 200.624 25.5093C199.643 26.4767 199.092 27.7892 199.091 29.1581C199.091 29.2928 199.096 29.4262 199.106 29.5582C198.493 29.8015 197.93 30.1658 197.453 30.6358C196.524 31.5526 196.001 32.7966 196 34.0939C196 36.7558 198.155 38.9208 200.836 38.981C200.936 38.9883 201.037 38.992 201.138 38.992Z" fill="#E5F9FF"/><g filter="url(#filter0_d_516_68689)"><rect x="55" y="46" width="107" height="39" rx="7" fill="#C3F0FF"/><rect x="55.5448" y="46.5448" width="105.91" height="37.9104" rx="6.45522" stroke="#2FC6F6" stroke-width="1.08955"/></g><g filter="url(#filter1_d_516_68689)"><rect x="55" y="71.0986" width="107" height="75" rx="7" fill="white"/><rect x="55.5448" y="71.6434" width="105.91" height="73.9104" rx="6.45522" stroke="#2FC6F6" stroke-width="1.08955"/></g><rect x="55.5448" y="69.2918" width="105.91" height="73.9104" rx="6.45522" fill="white" stroke="#2FC6F6" stroke-width="1.08955"/><rect x="55.5448" y="66.9403" width="105.91" height="73.9104" rx="6.45522" fill="white" stroke="#2FC6F6" stroke-width="1.08955"/><rect x="65.0547" y="77.626" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="65.0547" y="97.6152" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="65.0547" y="117.604" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="83.8125" y="77.626" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="83.8125" y="97.6152" width="11.7582" height="11.7582" rx="2.35165" fill="#2FC6F6"/><rect x="83.8125" y="117.604" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="102.57" y="77.626" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="102.57" y="97.6152" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="102.57" y="117.604" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="121.328" y="77.626" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="121.328" y="97.6152" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="140.086" y="77.626" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="140.086" y="97.6152" width="11.7582" height="11.7582" rx="2.35165" fill="#C3F0FF"/><rect x="74.9883" y="39" width="8.23077" height="21.1648" rx="4.11538" fill="#25B1DD" fill-opacity="0.14"/><rect x="74.9883" y="36.6484" width="8.23077" height="21.1648" rx="4.11538" fill="#1FCAFF"/><rect x="134.957" y="39" width="8.23077" height="21.1648" rx="4.11538" fill="#25B1DD" fill-opacity="0.14"/><rect x="134.957" y="36.6484" width="8.23077" height="21.1648" rx="4.11538" fill="#1FCAFF"/><defs><filter id="filter0_d_516_68689" x="43.46" y="36.73" width="130.08" height="62.08" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2.27"/><feGaussianBlur stdDeviation="5.77"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.16 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_516_68689"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_516_68689" result="shape"/></filter><filter id="filter1_d_516_68689" x="43.46" y="61.8286" width="130.08" height="98.08" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2.27"/><feGaussianBlur stdDeviation="5.77"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0.392157 0 0 0 0 0.427451 0 0 0 0 0.482353 0 0 0 0.16 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_516_68689"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_516_68689" result="shape"/></filter></defs></svg>',
	};

	module.exports = { EventListComponent, EventListItemType };
});

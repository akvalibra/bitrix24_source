import '../css/empty-state.css';

// @vue/component
export const EmptyState = {
	name: 'EmptyState',
	computed:
	{
		title(): string
		{
			return this.$Bitrix.Loc.getMessage('IM_SEARCH_EXPERIMENTAL_RESULT_NOT_FOUND');
		},
		subTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('IM_SEARCH_EXPERIMENTAL_RESULT_NOT_FOUND_DESCRIPTION');
		},
	},
	template: `
		<div class="bx-im-search-experimental-empty-state__container bx-im-search-experimental-empty-state__scope">
			<div class="bx-im-search-experimental-empty-state__icon"></div>
			<div class="bx-im-search-experimental-empty-state__title">
				{{ title }}
			</div>
			<div class="bx-im-search-experimental-empty-state__subtitle">
				{{ subTitle }}
			</div>
		</div>
	`,
};

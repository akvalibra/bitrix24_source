import { SidebarDetailBlock, SidebarFileTypes } from 'im.v2.const';

import { SidebarCollectionFormatter } from '../../classes/sidebar-collection-formatter';
import { FileMenu } from '../../classes/context-menu/file/file-menu';
import { DateGroup } from '../date-group';
import { DetailEmptyState } from '../detail-empty-state';
import { MediaDetailItem } from './item/media-detail-item';

import { SidebarDetail } from '../detail';
import '../../css/file/media-detail.css';

import type { ImModelSidebarFileItem } from 'im.v2.model';

// @vue/component
export const MediaDetail = {
	name: 'MediaDetail',
	components: { DateGroup, MediaDetailItem, DetailEmptyState, SidebarDetail },
	props: {
		dialogId: {
			type: String,
			required: true,
		},
		chatId: {
			type: Number,
			required: true,
		},
		service: {
			type: Object,
			required: true,
		},
	},
	computed:
	{
		SidebarDetailBlock: () => SidebarDetailBlock,
		files(): ImModelSidebarFileItem[]
		{
			return this.$store.getters['sidebar/files/get'](this.chatId, SidebarFileTypes.media);
		},
		formattedCollection(): Array
		{
			return this.collectionFormatter.format(this.files);
		},
		isEmptyState(): boolean
		{
			return this.formattedCollection.length === 0;
		},
	},
	created()
	{
		this.collectionFormatter = new SidebarCollectionFormatter();
		this.contextMenu = new FileMenu();
	},
	beforeUnmount()
	{
		this.collectionFormatter.destroy();
		this.contextMenu.destroy();
	},
	methods:
	{
		onScroll()
		{
			this.contextMenu.destroy();
		},
		onContextMenuClick(event, target)
		{
			const item = {
				...event,
				dialogId: this.dialogId,
			};

			this.contextMenu.openMenu(item, target);
		},
	},
	template: `
		<SidebarDetail
			:dialogId="dialogId"
			:chatId="chatId"
			:service="service"
			@scroll="onScroll"
			v-slot="slotProps"
			class="bx-im-sidebar-file-media-detail__scope"
		>
			<div v-for="dateGroup in formattedCollection" class="bx-im-sidebar-file-media-detail__date-group_container">
				<DateGroup :dateText="dateGroup.dateGroupTitle" />
				<div class="bx-im-sidebar-file-media-detail__items-group">
					<MediaDetailItem
						v-for="file in dateGroup.items"
						:fileItem="file"
						@contextMenuClick="onContextMenuClick"
					/>
				</div>
			</div>
			<DetailEmptyState
				v-if="!slotProps.isLoading && isEmptyState"
				:title="$Bitrix.Loc.getMessage('IM_SIDEBAR_FILES_EMPTY')"
				:iconType="SidebarDetailBlock.media"
			/>
		</SidebarDetail>
	`,
};

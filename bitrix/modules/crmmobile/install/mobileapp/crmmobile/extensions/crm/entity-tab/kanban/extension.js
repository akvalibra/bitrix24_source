/**
 * @module crm/entity-tab/kanban
 */
jn.define('crm/entity-tab/kanban', (require, exports, module) => {

	const { Loc } = require('loc');
	const { EntityTab } = require('crm/entity-tab');
	const { TypeSort } = require('crm/entity-tab/sort');
	const { ToolbarFactory } = require('crm/kanban/toolbar');
	const { CategorySelectActions } = require('crm/category-list/actions');
	const { CategoryListView } = require('crm/category-list-view');
	const { CategorySvg } = require('crm/assets/category');
	const { CategoryStorage } = require('crm/storage/category');
	const { CategoryCountersStoreManager } = require('crm/state-storage');
	const { Filter } = require('crm/entity-tab/filter');
	const { getSmartActivityMenuItem } = require('crm/entity-detail/component');
	const {
		get,
		isEqual,
	} = require('utils/object');

	const PULL_COMMAND = 'CRM_KANBANUPDATED';
	const PULL_EVENT_NAME_ITEM_ADDED = 'ITEMADDED';
	const PULL_EVENT_NAME_ITEM_UPDATED = 'ITEMUPDATED';

	const MAX_CATEGORY_CHANGE_ATTEMPTS = 3;
	const CATEGORY_CHANGE_DELAY_TIME = 2000;

	const pathToIcons = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/entity-tab/kanban/icons/`;

	/**
	 * @class KanbanTab
	 */
	class KanbanTab extends EntityTab
	{
		constructor(props)
		{
			super(props);

			const currentCategoryId = this.getCurrentCategoryId();

			this.category = this.getCategoryFromCategoryStorage(currentCategoryId);
			this.isEmptyAvailableCategories = false;
			this.needReloadTab = !this.category;
			this.categoryChangeAttempts = 0;
			this.categoryChangeTimeoutId = null;

			this.state.categoryId = currentCategoryId;
			this.state.isLoading = !this.category;

			this.onSelectedCategory = this.onSelectedCategoryHandler.bind(this);
			this.setSmartActivityStatus = this.setSmartActivityStatus.bind(this);
			this.onNotViewable = this.onNotViewableHandler.bind(this);
			this.setCounterFilter = this.setCounterFilter.bind(this);
			this.initCategoryCounters = this.initCategoryCounters.bind(this);
			this.onBeforeReload = this.onBeforeReloadHandler.bind(this);
			this.showCategorySelector = this.showCategorySelector.bind(this);

			this.toolbarFactory = new ToolbarFactory();
		}

		reload(params = {})
		{
			const viewComponent = this.getViewComponent();

			if (!viewComponent)
			{
				console.error('view component not found');
				return;
			}

			// @todo now support only deals
			if (viewComponent.setFilter)
			{
				viewComponent.setFilter(this.filter);
			}

			const skipFillSlides = BX.prop.getBoolean(params, 'skipFillSlides', false);
			const menuButtons = BX.prop.getArray(params, 'menuButtons', null);
			const skipUseCache = BX.prop.getBoolean(params, 'skipUseCache', false);
			const skipInitCounters = BX.prop.getBoolean(params, 'skipInitCounters', false);
			const force = BX.prop.getBoolean(params, 'force', false);
			const updateToolbarColumnId = BX.prop.getBoolean(params, 'updateToolbarColumnId', true);

			viewComponent.reload(viewComponent.getCurrentSlideName(), force, {
				skipFillSlides,
				menuButtons,
				skipUseCache,
				skipInitCounters,
				updateToolbarColumnId,
			});
		}

		initCategoryCounters(params = {})
		{
			CategoryCountersStoreManager.init(this.props.entityTypeId, this.getCurrentCategoryId(), params);
		}

		componentWillReceiveProps(nextProps)
		{
			super.componentWillReceiveProps(nextProps);

			if (this.entityTypeName !== nextProps.entityTypeName)
			{
				this.entityTypeName = nextProps.entityTypeName;
				this.state.categoryId = this.getCurrentCategoryId();
			}
		}

		componentDidMount()
		{
			super.componentDidMount();

			CategoryStorage
				.setEventId('crm.kanban')
				.subscribeOnChange(() => this.applyCategoryStorageChanges())
				.markReady()
			;

			BX.addCustomEvent('Crm.CategoryList::onSelectedCategory', this.onSelectedCategory);
			BX.addCustomEvent('Crm.Activity.Todo::onChangeNotifications', this.setSmartActivityStatus);

			if (!this.props.permissions.read)
			{
				const currentCategoryId = this.getCurrentCategoryId();
				const category = this.getCategoryFromCategoryStorage(currentCategoryId);
				this.changeCategory(category, true);
			}
		}

		onSelectedCategoryHandler(category)
		{
			if (this.state.categoryId !== category.id)
			{
				this.changeCategory(category);
			}
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			BX.removeCustomEvent('Crm.CategoryList::onSelectedCategory', this.onSelectedCategory);
			BX.removeCustomEvent('Crm.Activity.Todo::onChangeNotifications', this.setSmartActivityStatus);

			CategoryStorage.unsubscribe('crm.kanban');
		}

		applyCategoryStorageChanges()
		{
			const category = this.getCategoryFromCategoryStorage(this.state.categoryId);

			if (this.category === null)
			{
				this.changeCategory(category);
			}
			else if (!isEqual(this.category, category))
			{
				this.category = category;
			}
		}

		/**
		 * @param {Number|null} categoryId
		 * @returns {Object|null}
		 */
		getCategoryFromCategoryStorage(categoryId = null)
		{
			if (categoryId === null)
			{
				categoryId = this.getCurrentCategoryId();
			}

			return CategoryStorage.getCategory(this.props.entityTypeId, categoryId);
		}

		changeCategory(category, showNotice = false)
		{
			if (this.categoryChangeTimeoutId)
			{
				clearTimeout(this.categoryChangeTimeoutId);
			}

			const desiredCategoryId = category ? category.id : null;

			const promise = this.category ? new Promise(resolve => {
				this.setIsLoading().then(resolve);
			}) : Promise.resolve();

			promise
				.then(() => this.trySetCurrentCategory(category))
				.then(data => this.tryUpdateToNewCategory(data, desiredCategoryId, showNotice));
		}

		trySetCurrentCategory(category)
		{
			return new Promise(resolve => {
				const entityType = this.getCurrentEntityType();
				const categoryId = category ? category.id : -1;

				if (entityType.needSaveCurrentCategoryId)
				{
					const { entityTypeId } = this.props;
					BX.ajax.runAction('crmmobile.Category.set', {
						data: {
							entityTypeId,
							categoryId,
						},
					}).then(response => {
						if (categoryId !== response.data.categoryId && categoryId >= 0)
						{
							const pathToList = CategoryStorage.getPathToCategoryList(entityTypeId);
							CategoryStorage.clearTtlValue(pathToList);
						}

						resolve(response.data);
					}).catch(({ errors }) => {
						console.error(errors);
					});
				}
				else
				{
					resolve({
						categoryId,
					});
				}
			});
		}

		tryUpdateToNewCategory(data, desiredCategoryId, showNotice)
		{
			const { categoryId } = data;

			if (categoryId === null)
			{
				this.setState({
					isEmptyAvailableCategories: true,
					isLoading: false,
				});
				return;
			}

			const newState = {
				isLoading: false,
				searchButtonBackgroundColor: null,
			};

			if (categoryId === this.state.categoryId && this.category)
			{
				if (categoryId !== desiredCategoryId)
				{
					this.showChangeToAvailableCategoryNotice(this.category.name);
				}
				this.setState(newState, () => this.reload());
				return;
			}

			const categoryFromStorage = this.getCategoryFromCategoryStorage(categoryId);

			if (!categoryFromStorage)
			{
				this.state.categoryId = categoryId;
				this.clearCurrentCategory();

				console.error(`Category ${categoryId} not found in storage`);
				if (this.categoryChangeAttempts++ < MAX_CATEGORY_CHANGE_ATTEMPTS)
				{
					console.info(`Category change will be repeated after 5 seconds. Attempt: ${this.categoryChangeAttempts}`);
					this.categoryChangeTimeoutId = setTimeout(() => {
						this.tryUpdateToNewCategory(data, desiredCategoryId, showNotice);
					}, CATEGORY_CHANGE_DELAY_TIME * this.categoryChangeAttempts);
				}
				return;
			}

			this.categoryChangeAttempts = 0;

			showNotice = (showNotice || (categoryFromStorage && categoryFromStorage.id !== desiredCategoryId));

			if (showNotice)
			{
				this.showChangeToAvailableCategoryNotice(categoryFromStorage.name);
			}

			this.filter = new Filter();

			newState.searchButtonBackgroundColor = null;
			newState.categoryId = categoryId;
			this.setState(newState, () => this.initAfterCategoryChange(categoryFromStorage, data));
		}

		showChangeToAvailableCategoryNotice(categoryName)
		{
			const sectionDesc = BX.message('M_CRM_ENTITY_TAB_FORBIDDEN_READ_SECTION_DESC2');
			Notify.showUniqueMessage(
				sectionDesc.replace('#SECTION_NAME#', categoryName),
				BX.message('M_CRM_ENTITY_TAB_FORBIDDEN_READ_SECTION2'),
				{ time: 5 },
			);
		}

		clearCurrentCategory()
		{
			this.category = null;
		}

		initAfterCategoryChange(category, data)
		{
			this.filter = new Filter();
			this.category = category;
			this.isEmpty = true;
			this.floatingButtonMenu = null;
			let needInitMenu = true;

			this.updateEntityTypeData(data, (tabs) => {
				this.entityTypes = tabs;
				const params = {
					menuButtons: this.getMenuButtons(),
				}
				this.needReloadTab = false;
				needInitMenu = false;
				this.reload(params);
			});

			if (needInitMenu)
			{
				this.getCurrentStatefulList().initMenu();
			}

			if (this.needReloadTab)
			{
				this.needReloadTab = false;
				this.reload();
			}
		}

		/**
		 * @param {Object} data
		 * @param {Function|null} callback
		 */
		updateEntityTypeData(data, callback = null)
		{
			if (this.props.updateEntityTypeData)
			{
				this.props.updateEntityTypeData(this.props.entityTypeId, data, callback);
			}
		}

		render()
		{
			const { isLoading, isEmptyAvailableCategories } = this.state;

			let content;

			if (isEmptyAvailableCategories)
			{
				content = this.renderKanban({
					forbidden: true,
				});
			}
			else if (isLoading)
			{
				content = new LoadingScreenComponent();
			}
			else
			{
				content = this.renderKanban();
			}

			return View(
				this.getViewConfig(),
				content,
			);
		}

		renderKanban(config = {})
		{
			return new UI.Kanban({
				entityTypeName: this.entityTypeName,
				entityTypeId: this.props.entityTypeId,
				actions: this.props.actions,
				actionParams: this.prepareActionParams(),
				filterParams: this.getFilterParams(),
				layout: this.props.layout,
				layoutMenuActions: this.getMenuActions(),
				itemDetailOpenHandler: this.handleItemDetailOpen.bind(this),
				itemCounterLongClickHandler: this.getCounterLongClickHandler(),
				isShowFloatingButton: this.isShowFloatingButton(),
				floatingButtonClickHandler: this.handleFloatingButtonClick.bind(this),
				floatingButtonLongClickHandler: this.handleFloatingButtonLongClick.bind(this),
				onDetailCardUpdateHandler: this.onDetailCardUpdate,
				onDetailCardCreateHandler: this.onDetailCardCreate,
				onNotViewableHandler: this.onNotViewable,
				onPanListHandler: this.props.onPanList || null,
				initCountersHandler: this.initCategoryCounters,
				itemActions: this.getItemActions(),
				itemParams: this.props.itemParams,
				pull: this.getPullConfig(),
				layoutOptions: this.getLayoutOptions(),
				itemLayoutOptions: this.getItemLayoutOptions(),
				menuButtons: this.getMenuButtons(),
				cacheName: this.getKanbanCacheName(),
				getEmptyListComponent: this.getEmptyListComponent.bind(this),
				onBeforeReload: this.onBeforeReload,
				toolbarFactory: this.toolbarFactory,
				config: config,
				needInitMenu: this.props.needInitMenu,
				ref: ref => this.viewComponent = ref,
				getMenuButtons: this.getMenuButtons.bind(this),
			});
		}

		onNotViewableHandler()
		{
			const category = this.getCategoryFromCategoryStorage();
			this.changeCategory(category);
		}

		getKanbanCacheName()
		{
			const categoryId = (this.getCategoryId() || -1);
			return `${this.props.cacheName}.${categoryId}`;
		}

		/**
		 * @returns {Object}
		 */
		prepareActionParams()
		{
			const actionParams = super.prepareActionParams();
			actionParams.loadItems.extra = (actionParams.loadItems.extra || {});

			const categoryId = this.getCategoryId();
			if (Number.isInteger(categoryId))
			{
				actionParams.loadItems.extra.filterParams = actionParams.loadItems.extra.filterParams || {};
				actionParams.loadItems.extra.filterParams.CATEGORY_ID = categoryId;
			}

			const entityType = this.getCurrentEntityType();
			const { presetId } = entityType.data;

			this.filter.prepareActionParams(actionParams, presetId);

			return actionParams;
		}

		/**
		 * @returns Object
		 */
		getFilterParams()
		{
			const params = {};
			const categoryId = this.getCategoryId();

			if (Number.isInteger(categoryId))
			{
				params.CATEGORY_ID = categoryId;
			}

			return params;
		}

		getMenuButtons()
		{
			const buttons = super.getMenuButtons();
			buttons.push({
				svg: {
					content: CategorySvg.funnel(),
				},
				type: 'options',
				badgeCode: 'kanban_categories_selector',
				callback: this.showCategorySelector,
			});

			return buttons;
		}

		showCategorySelector()
		{
			const categoryId = this.getCategoryId();
			const entityType = this.getCurrentEntityType();

			if (categoryId === null || !entityType)
			{
				console.error('CategoryId or entityType is empty');
				return;
			}

			this.openCategoryList(categoryId, entityType);
		}

		openCategoryList(categoryId, entityType)
		{
			void CategoryListView.open({
				entityTypeId: entityType.id,
				currentCategoryId: categoryId,
				selectAction: CategorySelectActions.SelectCurrentCategory,

				// @todo so far only deals are supported
				needSaveCurrentCategoryId: entityType.needSaveCurrentCategoryId,
			});
		}

		getAdditionalParamsForItem()
		{
			const viewComponent = this.getViewComponent();
			return viewComponent ? viewComponent.getAdditionalParamsForItem() : {};
		}

		/**
		 * @param {string} prefix
		 * @returns {string}
		 */
		getPullCommand(prefix)
		{
			const { entityTypeName } = this.props;
			const categoryId = this.getCategoryId();
			return `${prefix}_${entityTypeName}_${categoryId}`;
		}

		/**
		 * @returns {null|number}
		 */
		getCategoryId()
		{
			if (Number.isInteger(this.state.categoryId))
			{
				return this.state.categoryId;
			}

			return this.getCurrentCategoryId();
		}

		/**
		 * @returns {null|number}
		 */
		getCurrentCategoryId()
		{
			const currentEntityType = this.getCurrentEntityType();

			if (!this.hasCurrentCategoryIdInEntityType(currentEntityType))
			{
				return null;
			}

			return Number(currentEntityType.data.currentCategoryId);
		}

		/**
		 * @param {string|null} entityType
		 * @returns {boolean}
		 */
		hasCurrentCategoryIdInEntityType(entityType)
		{
			return (
				entityType
				&& entityType.data
				&& Number.isInteger(entityType.data.currentCategoryId)
			);
		}

		deleteItem(itemId)
		{
			const params = {
				eventId: this.pullManager.registerRandomEventId(),
			};

			this.getViewComponent().deleteItem(itemId, params);
		}

		updateItemColumn(itemId, columnName)
		{
			this.getViewComponent().updateItemColumn(itemId, columnName);
		}

		getCurrentStatefulList()
		{
			return this.getViewComponent().getCurrentStatefulList();
		}

		/**
		 * @param {Object} data
		 * @param {Object} context
		 * @returns {Boolean}
		 */
		isNeedProcessPull(data, context)
		{
			const { command, params } = data;

			if (this.pullManager.hasEvent(params.eventId))
			{
				return false;
			}

			if (command !== this.getPullCommand(PULL_COMMAND))
			{
				return false;
			}

			const viewComponent = this.getViewComponent();

			const isUpdatedItemInCurrentSlide = (params, slideName) => {
				return (
					params.eventName !== PULL_EVENT_NAME_ITEM_ADDED
					&& viewComponent.getCurrentSlideName() === context.slideName
					&& this.hasItemInCurrentColumn(params.item.id)
				);
			};

			const columnId = get(params, 'item.data.columnId', '');

			const isCurrentSlide = (
				this.isCurrentSlideName(params.item.data.columnId, context.slideName)
				|| isUpdatedItemInCurrentSlide(params, context.slideName)
			);
			const isAllStagesSlide = (viewComponent.getCurrentSlideName() === viewComponent.getSlideName());

			if (!isCurrentSlide && !isAllStagesSlide)
			{
				return false;
			}

			if (this.hasItemInCurrentColumn(params.item.id))
			{
				return true;
			}

			if (
				params.eventName === PULL_EVENT_NAME_ITEM_UPDATED
				&& (viewComponent.getSlideName(columnId) === context.slideName || isAllStagesSlide)
			)
			{
				return true;
			}

			return (params.eventName === PULL_EVENT_NAME_ITEM_ADDED);
		}

		/**
		 * @param {string} itemColumnId
		 * @param {string} slideName
		 * @returns {boolean}
		 */
		isCurrentSlideName(itemColumnId, slideName)
		{
			const currentSlideName = this.getViewComponent().getCurrentSlideName();

			return (
				slideName === this.getViewComponent().getSlideName(itemColumnId)
				&& slideName === currentSlideName
			);
		}

		getEmptyColumnScreenConfig(model)
		{
			const kanban = this.getViewComponent();
			const slideName = kanban.getCurrentSlideName();
			const columnStatusId = kanban.getColumnStatusIdFromSlideName(slideName);
			const currentColumn = kanban.getColumnByName(columnStatusId);

			return model.getEmptyColumnScreenConfig({
				column: currentColumn,
			});
		}

		isWrongPullContext(context = {})
		{
			return (this.getViewComponent().getCurrentSlideName() !== context.slideName);
		}

		scrollToTop()
		{
			const simpleList = this.getViewComponent().getCurrentStatefulList().getSimpleList();
			this.scrollSimpleListToTop(simpleList);
		}

		getMenuActions()
		{
			let menuActions = [
				this.getSortMenuAction(),
			];

			const smartActivityAction = this.getSmartActivityAction();
			if (smartActivityAction)
			{
				menuActions.push(smartActivityAction);
			}

			const parentMenu = super.getMenuActions();
			if (parentMenu[0])
			{
				parentMenu[0].showTopSeparator = true;
				menuActions = menuActions.concat(parentMenu);
			}

			return menuActions;
		}

		getSortMenuAction()
		{
			const currentSortType = this.getCurrentTypeSort();

			return {
				id: 'kanban-sort-toggle',
				title: Loc.getMessage('M_CRM_ENTITY_TAB_SORT_TOGGLE_TEXT'),
				checked: (currentSortType === TypeSort.LastActivityTime),
				sectionCode: 'sort',
				onItemSelected: () => this.setSortType(),
				iconUrl: pathToIcons + 'focus.png',
			};
		}

		getCurrentTypeSort()
		{
			const { sortType } = this.getCurrentEntityType().data;

			return sortType || TypeSort.LastActivityTime;
		}

		setSortType()
		{
			const type = (
				this.getCurrentTypeSort() === TypeSort.LastActivityTime
				? TypeSort.Id
				: TypeSort.LastActivityTime
			);

			BX.ajax.runAction(this.props.actions.setSortType, {
				data: {
					entityTypeId: this.props.entityTypeId,
					categoryId: this.getCategoryId(),
					type,
				},
			}).then(response => {
				if (response.errors.length)
				{
					console.error(response);
					return;
				}

				this.updateEntityTypeData({
					sortType: type,
				}, () => {
					this.reload({
						skipFillSlides: true,
						skipUseCache: true,
						updateToolbarColumnId: false,
						skipInitCounters: true,
						force: true,
					});
				});
			}).catch((response) => {
				console.error(response);
			});
		}

		getSmartActivityAction()
		{
			const smartActivitySettings = this.getSmartActivitySettings();
			if (!smartActivitySettings)
			{
				return null;
			}

			return getSmartActivityMenuItem(!smartActivitySettings.skipped);
		}

		getSmartActivitySettings()
		{
			const { smartActivitySettings } = this.getCurrentEntityType().data;

			return smartActivitySettings;
		}

		setSmartActivityStatus(status)
		{
			const smartActivitySettings = this.getSmartActivitySettings();
			if (!smartActivitySettings)
			{
				return;
			}

			const skipped = !status;

			if (smartActivitySettings.skipped === skipped)
			{
				return;
			}

			smartActivitySettings.skipped = skipped;

			this.getCurrentStatefulList().initMenu({
				layoutMenuActions: this.getMenuActions(),
			});
		}

		getPullItemConfig(item)
		{
			const itemConfig = super.getPullItemConfig(item);
			if (itemConfig.showReloadListNotification)
			{
				const kanban = this.getViewComponent();
				const currentSlideName = kanban.getCurrentSlideName();
				if (
					currentSlideName !== kanban.getSlideName()
					&& currentSlideName !== kanban.getSlideName(item.data.columnId)
				)
				{
					itemConfig.showReloadListNotification = false;
				}
			}

			return itemConfig;
		}

		getEmptyListComponent()
		{
			if (this.filter.isActive())
			{
				return super.getEmptyListComponent();
			}

			const viewComponent = this.getViewComponent();

			if (
				this.getCurrentEntityType().isStagesEnabled
				&& viewComponent.getSlideName() === viewComponent.getCurrentSlideName()
			)
			{
				this.isEmpty = !Boolean(this.getCurrentStatefulList().getItems().size);
			}

			return super.getEmptyListComponent();
		}

		onBeforeReloadHandler()
		{
			const sortType = this.getCurrentTypeSort();
			if (sortType === TypeSort.Id)
			{
				return {
					reload: false,
					animate: true,
				};
			}

			return {
				reload: false,
				animate: !this.getCurrentStatefulList().getSimpleList().shouldShowReloadListNotification()
			}
		}
	}

	module.exports = { KanbanTab };
});
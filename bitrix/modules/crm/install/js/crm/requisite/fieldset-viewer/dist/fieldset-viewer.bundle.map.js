{"version":3,"sources":["fieldset-viewer.bundle.js"],"names":["this","BX","Crm","exports","main_core","main_core_events","main_popup","main_loader","ui_buttons","crm_field_listEditor","_","t","_t","_t2","_t3","_t4","_t5","_t6","_t7","_t8","FieldsetViewer","EventEmitter","constructor","options","super","cache","Cache","MemoryCache","setEventNamespace","subscribeFromOptions","events","setOptions","Event","bind","bindElement","onBindElementClick","setData","data","set","getData","get","load","Promise","resolve","entityTypeId","entityId","getOptions","ajax","runAction","json","then","result","getPopup","remember","Popup","autoHide","width","height","className","noAllPaddings","Type","isPlainObject","popupOptions","onClose","emit","changed","getIsChanged","setIsChanged","value","Text","toBoolean","getLoader","Loader","show","popup","Dom","clean","getContentContainer","setContent","createPopupContent","hide","close","event","preventDefault","Tag","render","createBannerLayout","createListLayout","getFooterLayout","createCloseButton","title","Loc","getMessage","replace","encode","description","text","isStringFilled","more","createListContainer","fields","map","createListItem","editButton","_options$editing","editing","url","onEditButtonClick","_options$editing2","SidePanel","Instance","open","cacheable","label","onCloseClick","getFieldListEditor","ListEditor","setId","id","editable","type","required","autoSave","onSave","fieldsPanelOptions","hideVirtual","fieldListEditorOptions","getEditButton","Button","color","Color","LIGHT_BORDER","icon","Icon","EDIT","size","Size","SMALL","round","click","showSlider","Requisite","Main","UI","Field"],"mappings":"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","file":"fieldset-viewer.bundle.map.js"}
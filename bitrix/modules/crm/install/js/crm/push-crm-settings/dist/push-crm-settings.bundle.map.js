{"version":3,"sources":["push-crm-settings.bundle.js"],"names":["this","BX","exports","main_core_events","crm_activity_todoNotificationSkipMenu","main_popup","crm_kanban_sort","crm_kanban_restriction","main_core","requireClassOrNull","param","constructor","paramName","Type","isNil","requireClass","Error","name","getType","requireStringOrNull","isStringFilled","value","isObject","isPlainObject","_value$constructor","EntityType","Reflection","getClass","CHECKED_CLASS","NOT_CHECKED_CLASS","_entityTypeId","babelHelpers","classPrivateFieldLooseKey","_rootMenu","_targetItemId","_controller","_restriction","_todoSkipMenu","_isSetSortRequestRunning","_bindEvents","_shouldShowPushCrmSettings","_getItems","_shouldShowLastActivitySortToggle","_getLastActivitySortToggle","_isLastActivitySortEnabled","_handleLastActivitySortToggleClick","_shouldShowTodoSkipMenu","PushCrmSettings","params","Object","defineProperty","_shouldShowTodoSkipMenu2","_handleLastActivitySortToggleClick2","_isLastActivitySortEnabled2","_getLastActivitySortToggle2","_shouldShowLastActivitySortToggle2","_getItems2","_shouldShowPushCrmSettings2","_bindEvents2","writable","classPrivateFieldLooseBase","Text","toInteger","entityTypeId","isDefined","rootMenu","Menu","targetItemId","controller","SettingsController","restriction","Restriction","TodoNotificationSkipMenu","selectedValue","todoCreateNotificationSkipPeriod","onPopupShowHandler","event","popup","getTarget","getId","EventEmitter","unsubscribe","GLOBAL_TARGET","item","addMenuItem","text","Loc","getMessage","items","id","subscribe","_target$getSubMenu2","target","itemOptionsToAdd","_target$getSubMenu","getSubMenu","removeMenuItem","length","push","getItems","_babelHelpers$classPr","_babelHelpers$classPr2","getCurrentSettings","isTypeSupported","BY_LAST_ACTIVITY_TIME","isSortTypeChangeAvailable","disabled","className","onclick","bind","_babelHelpers$classPr3","getCurrentType","_item$getMenuWindow","_item$getMenuWindow$g","getMenuWindow","getRootMenuWindow","close","disable","settings","newSortType","getSupportedTypes","find","sortType","setCurrentSortType","then","catch","finally","enable","allowedTypes","enumeration","deal","includes","namespace","Crm","Event","Activity","Main","CRM","Kanban"],"mappings":"AAAAA,KAAKC,GAAKD,KAAKC,IAAM,IACpB,SAAUC,EAAQC,EAAiBC,EAAsCC,EAAWC,EAAgBC,EAAuBC,GAC3H,aAEA,SAASC,EAAmBC,EAAOC,EAAaC,GAC9C,GAAIJ,EAAUK,KAAKC,MAAMJ,GAAQ,CAC/B,OAAOA,EAGT,OAAOK,EAAaL,EAAOC,EAAaC,GAE1C,SAASG,EAAaL,EAAOC,EAAaC,GACxC,GAAIF,aAAiBC,EAAa,CAChC,OAAOD,EAGT,MAAM,IAAIM,MAAM,YAAYJ,uBAA+BD,EAAYM,aAAaC,EAAQR,cAE9F,SAASS,EAAoBT,EAAOE,GAClC,GAAIJ,EAAUK,KAAKO,eAAeV,IAAUF,EAAUK,KAAKC,MAAMJ,GAAQ,CACvE,OAAOA,EAGT,MAAM,IAAIM,MAAM,YAAYJ,6CAAqDM,EAAQR,cAG3F,SAASQ,EAAQG,GACf,GAAIb,EAAUK,KAAKS,SAASD,KAAWb,EAAUK,KAAKU,cAAcF,GAAQ,CAC1E,IAAIG,EAEJ,OAAQH,GAAS,UAAY,GAAKG,EAAqBH,EAAMV,cAAgB,UAAY,EAAIa,EAAmBP,OAAS,UAI3H,cAAcI,EAGhB,MAAMI,EAAajB,EAAUkB,WAAWC,SAAS,oBACjD,MAAMC,EAAgB,yBACtB,MAAMC,EAAoB,uBAK1B,IAAIC,EAA6BC,aAAaC,0BAA0B,gBAExE,IAAIC,EAAyBF,aAAaC,0BAA0B,YAEpE,IAAIE,EAA6BH,aAAaC,0BAA0B,gBAExE,IAAIG,EAA2BJ,aAAaC,0BAA0B,cAEtE,IAAII,EAA4BL,aAAaC,0BAA0B,eAEvE,IAAIK,EAA6BN,aAAaC,0BAA0B,gBAExE,IAAIM,EAAwCP,aAAaC,0BAA0B,2BAEnF,IAAIO,EAA2BR,aAAaC,0BAA0B,cAEtE,IAAIQ,EAA0CT,aAAaC,0BAA0B,6BAErF,IAAIS,EAAyBV,aAAaC,0BAA0B,YAEpE,IAAIU,EAAiDX,aAAaC,0BAA0B,oCAE5F,IAAIW,EAA0CZ,aAAaC,0BAA0B,6BAErF,IAAIY,EAA0Cb,aAAaC,0BAA0B,6BAErF,IAAIa,EAAkDd,aAAaC,0BAA0B,qCAE7F,IAAIc,EAAuCf,aAAaC,0BAA0B,0BAElF,MAAMe,EACJpC,YAAYqC,GACVC,OAAOC,eAAelD,KAAM8C,EAAyB,CACnDzB,MAAO8B,IAETF,OAAOC,eAAelD,KAAM6C,EAAoC,CAC9DxB,MAAO+B,IAETH,OAAOC,eAAelD,KAAM4C,EAA4B,CACtDvB,MAAOgC,IAETJ,OAAOC,eAAelD,KAAM2C,EAA4B,CACtDtB,MAAOiC,IAETL,OAAOC,eAAelD,KAAM0C,EAAmC,CAC7DrB,MAAOkC,IAETN,OAAOC,eAAelD,KAAMyC,EAAW,CACrCpB,MAAOmC,IAETP,OAAOC,eAAelD,KAAMwC,EAA4B,CACtDnB,MAAOoC,IAETR,OAAOC,eAAelD,KAAMuC,EAAa,CACvClB,MAAOqC,IAETT,OAAOC,eAAelD,KAAM8B,EAAe,CACzC6B,SAAU,KACVtC,WAAY,IAEd4B,OAAOC,eAAelD,KAAMiC,EAAW,CACrC0B,SAAU,KACVtC,WAAY,IAEd4B,OAAOC,eAAelD,KAAMkC,EAAe,CACzCyB,SAAU,KACVtC,WAAY,IAEd4B,OAAOC,eAAelD,KAAMmC,EAAa,CACvCwB,SAAU,KACVtC,WAAY,IAEd4B,OAAOC,eAAelD,KAAMoC,EAAc,CACxCuB,SAAU,KACVtC,WAAY,IAEd4B,OAAOC,eAAelD,KAAMqC,EAAe,CACzCsB,SAAU,KACVtC,WAAY,IAEd4B,OAAOC,eAAelD,KAAMsC,EAA0B,CACpDqB,SAAU,KACVtC,MAAO,QAETU,aAAa6B,2BAA2B5D,KAAM8B,GAAeA,GAAiBtB,EAAUqD,KAAKC,UAAUd,EAAOe,cAE9G,GAAItC,IAAeA,EAAWuC,UAAUjC,aAAa6B,2BAA2B5D,KAAM8B,GAAeA,IAAiB,CACpH,MAAM,IAAId,MAAM,qCAAqCe,aAAa6B,2BAA2B5D,KAAM8B,GAAeA,MAGpHC,aAAa6B,2BAA2B5D,KAAMiC,GAAWA,GAAalB,EAAaiC,EAAOiB,SAAU5D,EAAW6D,KAAM,mBACrHnC,aAAa6B,2BAA2B5D,KAAMkC,GAAeA,GAAiBf,EAAoB6B,EAAOmB,aAAc,uBACvHpC,aAAa6B,2BAA2B5D,KAAMmC,GAAaA,GAAe1B,EAAmBuC,EAAOoB,WAAY9D,EAAgB+D,mBAAoB,qBACpJtC,aAAa6B,2BAA2B5D,KAAMoC,GAAcA,GAAgB3B,EAAmBuC,EAAOsB,YAAa/D,EAAuBgE,YAAa,sBACvJxC,aAAa6B,2BAA2B5D,KAAMqC,GAAeA,GAAiB,IAAIjC,EAAsCoE,yBAAyB,CAC/IT,aAAchC,aAAa6B,2BAA2B5D,KAAM8B,GAAeA,GAC3E2C,cAAetD,EAAoB6B,EAAO0B,iCAAkC,6CAG9E3C,aAAa6B,2BAA2B5D,KAAMuC,GAAaA,MAK/D,SAASmB,IACP,MAAMiB,EAAqBC,IACzB,MAAMC,EAAQD,EAAME,YAEpB,GAAID,EAAME,UAAYhD,aAAa6B,2BAA2B5D,KAAMiC,GAAWA,GAAW8C,QAAS,CACjG,OAIF5E,EAAiB6E,aAAaC,YAAY9E,EAAiB6E,aAAaE,cAAe,cAAeP,GAEtG,IAAK5C,aAAa6B,2BAA2B5D,KAAMwC,GAA4BA,KAA+B,CAC5G,OAGF,MAAM2C,EAAOpD,aAAa6B,2BAA2B5D,KAAMiC,GAAWA,GAAWmD,YAAY,CAC3FC,KAAM7E,EAAU8E,IAAIC,WAAW,wCAE/BC,MAAO,CAAC,CACNC,GAAI,UAEL1D,aAAa6B,2BAA2B5D,KAAMkC,GAAeA,IAEhEiD,EAAKO,UAAU,kBAAkBd,IAC/B,IAAIe,EAEJ,MAAMC,EAAShB,EAAME,YAErB,IAAK,MAAMe,KAAoB9D,aAAa6B,2BAA2B5D,KAAMyC,GAAWA,KAAc,CACpG,IAAIqD,GAEHA,EAAqBF,EAAOG,eAAiB,UAAY,EAAID,EAAmBV,YAAYS,IAG9FF,EAAsBC,EAAOG,eAAiB,UAAY,EAAIJ,EAAoBK,eAAe,YAItG7F,EAAiB6E,aAAaU,UAAUvF,EAAiB6E,aAAaE,cAAe,cAAeP,GAGtG,SAASlB,IACP,OAAO1B,aAAa6B,2BAA2B5D,KAAMyC,GAAWA,KAAawD,OAAS,EAGxF,SAASzC,IACP,MAAMgC,EAAQ,GAEd,GAAIzD,aAAa6B,2BAA2B5D,KAAM0C,GAAmCA,KAAsC,CACzH8C,EAAMU,KAAKnE,aAAa6B,2BAA2B5D,KAAM2C,GAA4BA,MAGvF,GAAIZ,aAAa6B,2BAA2B5D,KAAM8C,GAAyBA,KAA4B,CACrG0C,EAAMU,QAAQnE,aAAa6B,2BAA2B5D,KAAMqC,GAAeA,GAAe8D,YAG5F,OAAOX,EAGT,SAASjC,IACP,IAAI6C,EAAuBC,EAE3B,UAAWD,EAAwBrE,aAAa6B,2BAA2B5D,KAAMmC,GAAaA,KAAiB,MAAQiE,EAAsBE,qBAAqBC,gBAAgBjG,EAAgBO,KAAK2F,yBAA2BH,EAAyBtE,aAAa6B,2BAA2B5D,KAAMoC,GAAcA,KAAkB,MAAQiE,EAAuBI,6BAG1W,SAASnD,IACP,MAAO,CACL+B,KAAM7E,EAAU8E,IAAIC,WAAW,0CAC/BmB,SAAU3E,aAAa6B,2BAA2B5D,KAAMsC,GAA0BA,GAClFqE,UAAW5E,aAAa6B,2BAA2B5D,KAAM4C,GAA4BA,KAAgChB,EAAgBC,EACrI+E,QAAS7E,aAAa6B,2BAA2B5D,KAAM6C,GAAoCA,GAAoCgE,KAAK7G,OAIxI,SAASqD,IACP,IAAIyD,EAEJ,QAASA,EAAyB/E,aAAa6B,2BAA2B5D,KAAMmC,GAAaA,KAAiB,UAAY,EAAI2E,EAAuBR,qBAAqBS,oBAAsBzG,EAAgBO,KAAK2F,sBAGvN,SAASpD,EAAoCwB,EAAOO,GAClD,IAAI6B,EAAqBC,GAExBD,EAAsB7B,EAAK+B,kBAAoB,UAAY,GAAKD,EAAwBD,EAAoBG,sBAAwB,UAAY,EAAIF,EAAsBG,QAC3KjC,EAAKkC,UAEL,GAAItF,aAAa6B,2BAA2B5D,KAAMsC,GAA0BA,GAA2B,CACrG,OAGFP,aAAa6B,2BAA2B5D,KAAMsC,GAA0BA,GAA4B,KAEpG,MAAMgF,EAAWvF,aAAa6B,2BAA2B5D,KAAMmC,GAAaA,GAAamE,qBAEzF,IAAIiB,EAEJ,GAAID,EAASP,mBAAqBzG,EAAgBO,KAAK2F,sBAAuB,CAE5Ee,EAAcD,EAASE,oBAAoBC,MAAKC,GAAYA,IAAapH,EAAgBO,KAAK2F,4BACzF,CACLe,EAAcjH,EAAgBO,KAAK2F,sBAGrCzE,aAAa6B,2BAA2B5D,KAAMmC,GAAaA,GAAawF,mBAAmBJ,GAAaK,MAAK,SAAUC,OAAM,SAAUC,SAAQ,KAC7I/F,aAAa6B,2BAA2B5D,KAAMsC,GAA0BA,GAA4B,MACpG6C,EAAK4C,YAIT,SAAS5E,IACP,IAAI6E,EAAe,GAEnB,GAAIvG,EAAY,CACduG,EAAe,CAACvG,EAAWwG,YAAYC,MAGzC,OAAOF,EAAaG,SAASpG,aAAa6B,2BAA2B5D,KAAM8B,GAAeA,IAG5F,MAAMsG,EAAY5H,EAAUkB,WAAW0G,UAAU,UACjDA,EAAUrF,gBAAkBA,EAE5B7C,EAAQ6C,gBAAkBA,GA7Q3B,CA+QG/C,KAAKC,GAAGoI,IAAMrI,KAAKC,GAAGoI,KAAO,GAAIpI,GAAGqI,MAAMrI,GAAGoI,IAAIE,SAAStI,GAAGuI,KAAKvI,GAAGwI,IAAIC,OAAOzI,GAAGwI,IAAIC,OAAOzI","file":"push-crm-settings.bundle.map.js"}
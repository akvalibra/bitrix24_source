{"version":3,"sources":["extension.js"],"names":["include","pathToExtension","apiVersion","Application","getApiVersion","platform","getPlatform","Request","constructor","namespace","this","restNamespace","call","methodName","params","method","currentAnswer","abortCurrentRequest","Promise","resolve","reject","console","log","BX","rest","callMethod","response","error","answer","onRequestCreate","bind","ajax","currentAjaxObject","abort","Options","storageName","defaultOptions","swipeShowHelper","value","limit","get","storage","getObject","set","options","setObject","update","optionName","optionValue","updateObject","TaskCardHandler","taskCard","closeForm","back","setTaskInfo","taskInfo","TaskCard","selectFields","queryParams","GET_TASK_LIMIT_EXCEEDED","WITH_RESULT_INFO","static","id","title","message","code","user","name","NAME","LAST_NAME","trim","EMAIL","s4","Math","floor","random","toString","substring","icon","defaultImage","imageUrl","link","group","image","taskcard","userId","init","setListeners","parseInt","componentParameters","taskId","guid","taskObjectData","currentUser","result","settings","userInfo","deadlines","task","Task","cloneData","taskCardHandler","taskPopupMenu","addCustomEvent","eventData","onPageLoaded","onMobileGridFormDataChange","onTaskDetailOptionsButtonClick","onCommentsRead","onCommentAction","onItemAction","action","onItemChecked","item","checked","handlers","task_update","onPullUpdate","task_remove","onPullDelete","comment_add","onPullComment","task_result_create","onPullTaskResultCreate","task_result_delete","onPullTaskResultDelete","command","apply","then","taskLimitExceeded","redrawTaskPopupMenu","nodeName","nodeValue","dateValue","responsibleIcon","formId","replace","updateTask","deadline","Date","parse","activityDate","now","formattedIcon","responsible","IMAGE_RESIZE","FILTER","ID","getFormattedName","encodeURI","PERSONAL_PHOTO","show","pseudoRead","updateTaskCardInfo","onChangeDeadlineAction","onApproveAction","onDisapproveAction","complete","data","updateTaskResultData","updateData","taskRequireResult","taskHasResult","entityType","entityId","entityXmlId","split","Number","ownerId","select","setData","TASK_ID","updateCommentExists","InAppNotifier","showNotification","backgroundColor","fields","onViewLoaded","has","Object","prototype","hasOwnProperty","keys","forEach","key","getPrototypeOf","getTaskInfo","handleItemActions","project","notAllowedActions","actions","filter","includes","identifier","leftSwipeActions","position","length","swipeActions","concat","more","popupActions","push","iconUrl","popupImageUrls","textColor","sectionCode","cancel","map","menuMode","handleSwipeActionsShow","showSwipeActions","obj","isChecked","onCompleteAction","onRenewAction","onPingAction","onStartAction","onPauseAction","onChangeResponsibleAction","onDelegateAction","onChangeGroupAction","onMuteAction","onUnmuteAction","onUnfollowAction","onRemoveAction","onMoreAction","ping","Notify","showIndicatorSuccess","text","hideAfter","pickerParams","type","items","dialogs","showDatePicker","eventName","newTs","sendOnItemActionWebEvent","RecipientSelector","setSingleChoose","setTitle","setSelected","open","recipients","getItemDataFromUser","delegate","postWebEvent","selected","groupId","getItemDataFromGroup","isMuted","mute","unmute","taskItemData","actionsPopup","createPopupMenu","onActionsPopupItemSelected","setPosition","openNewTaskPage","getGuid","url","taskPaths","add","getTime","openTaskPage","openNewSubTaskPage","addSub","openEditTaskPage","PageManager","openComponent","componentCode","scriptPath","availableComponents","publicUrl","rootWidget","objectName","modal","cache","page","titleParams","MODE","COMPONENT_CODE","USER_ID","GUID","openPage","onAddToFavoriteAction","rawAccess","favorite","onRemoveFromFavoriteAction","remove","approve","disapprove","renew","status","statusList","completed","sendOnItemActionStatusWebEvent","start","pending","pause","inprogress","isRequireResult","isHasResult","oldStatus","showActionSheet","callback","getRemoveSheetItems","currentUserId","auditors","isMember","stopWatch","values","taskGuid","popupMenuItems","itemsMap","popupMenuItemsMap","can","disable","right","Boolean","urlPrefix","addTask","addSubTask","TaskCardView","super","mode","onInitSuccess","checklistController","ChecklistController","setRightButtons","TaskCardEdit","setLeftButtons","close","jnexport"],"mappings":"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","file":"extension.map.js"}
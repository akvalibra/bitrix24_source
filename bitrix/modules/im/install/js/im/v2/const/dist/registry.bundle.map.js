{"version":3,"sources":["registry.bundle.js"],"names":["this","BX","Messenger","v2","exports","DateFormat","Object","freeze","groupTitle","message","recentTitle","recentLinesTitle","readedTitle","default","vacationTitle","DeviceType","mobile","desktop","DeviceOrientation","horizontal","portrait","MutationType","none","add","update","delete","set","setAfter","setBefore","StorageLimit","dialogues","messages","Settings","darkTheme","SettingsMap","enableDarkTheme","AvatarSize","S","M","L","OpenTarget","current","auto","BotType","bot","network","support24","RestMethod","imMessageAdd","imMessageUpdate","imMessageDelete","imMessageLike","imMessageCommand","imMessageShare","imChatGet","imChatLeave","imChatMute","imChatParentJoin","imDialogGet","imDialogMessagesGet","imDialogRead","imDialogUnread","imDialogWriting","imDialogRestrictionsGet","imUserGet","imUserListGet","imDiskFolderGet","imDiskFileUpload","imDiskFileCommit","mobileBrowserConstGet","imRecentGet","imRecentList","imRecentPin","imCallGetCallLimits","imNotifyGet","imNotifySchemaGet","RestMethodHandler","imDialogMessagesGetInit","imDialogMessagesGetUnread","PullCommand","messageUpdate","messageDelete","PullHandlers","recent","EventType","dialog","open","call","openHistory","hide","leave","newMessage","scrollOnStart","scrollToBottom","readVisibleMessages","requestUnread","readMessage","quoteMessage","clickOnCommand","clickOnMention","clickOnUserName","clickOnMessageMenu","clickOnMessageRetry","clickOnUploadCancel","clickOnReadList","setMessageReaction","openMessageReactionList","clickOnKeyboardButton","clickOnChatTeaser","clickOnDialog","quotePanelClose","beforeMobileKeyboard","messagesSet","settingsChange","closePopup","textarea","focus","setFocus","blur","setBlur","keyUp","edit","insertText","sendMessage","fileSelected","startWriting","stopWriting","appButtonClick","uploader","addMessageWithFile","conference","setPasswordFocus","hideSmiles","requestPermissions","waitForStart","userRenameFocus","userRenameBlur","notification","updateState","setText","openUserList","search","selectItem","openSearch","updateSearch","closeSearch","requestUser","setCounter","setMessage","hideChat","leaveChat","clearLike","setDraftMessage","DialogType","private","chat","crm","announcement","DialogCrmType","lead","company","contact","deal","DialogReferenceClassName","listBody","listItem","listItemName","listItemBody","listUnreadLoader","DialogTemplateType","delimiter","group","historyLoader","unreadLoader","button","placeholder","DialogState","loading","empty","show","FileStatus","upload","wait","done","error","FileType","image","video","audio","file","MessageType","self","opponent","system","ConferenceFieldState","view","create","ConferenceStateType","preparation","ConferenceErrorCode","userLimitReached","detectIntranetUser","bitrix24only","kickedFromCall","unsupportedBrowser","missingMicrophone","unsafeConnection","wrongAlias","notStarted","finished","userLeftCall","noSignalFromCamera","ConferenceRightPanelMode","hidden","users","split","ConferenceUserState","Idle","Busy","Calling","Unavailable","Declined","Ready","Connecting","Connected","Failed","ChatTypes","user","videoconf","notifier","question","calendar","task","UserStatus","online","mobileOnline","idle","dnd","away","break","RecentSection","general","pinned","MessageStatus","received","delivered","RecentCallStatus","waiting","joined","RecentSettings","showBirthday","showInvited","showLastMessage","RecentSettingsMap","viewBirthday","viewCommonUsers","viewLastMessage","NotificationTypesCodes","confirm","simple","ChatOption","avatar","extend","leaveOwner","mute","rename","send","userList","Const"],"mappings":"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","file":"registry.bundle.map.js"}
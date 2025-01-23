'use strict';

module.exports = function (oAppData) {
	var
		$ = require('jquery'),
		ko = require('knockout'),

		TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),

		bInitialized = false
	;

	Settings.init(oAppData);

	if (App.isMobile()) {
		if (App.isUserNormalOrTenant()) {
			return {
				start: function (ModulesManager) {
					App.subscribeEvent('MailMobileWebclient::ConstructView::after', function (oParams) {
						if (ko.isSubscribable(oParams.View.selectedPanel) && !bInitialized) {

							var sHtml = '';
							sHtml += '<span class="banner" style="display: block; text-align: center;">';
							sHtml += Settings.BannerLink !== '' ? '<a href="' + Settings.BannerLink + '" target="_blank">' : '';
							sHtml += Settings.BannerUrlMobile !== '' ? '<img src="' + Settings.BannerUrlMobile + '" />' : '';
							sHtml += Settings.ShowTitle ? '<span class="link">' + TextUtils.i18n('%MODULENAME%/UPRGADE_NOW') + '</span>' : '';
							sHtml += Settings.BannerLink !== '' ? '</a>' : '';
							sHtml += '</span>';

							var oBannerEl = $(sHtml);
							oParams.View.selectedPanel.subscribe(function (value) {
								if (!bInitialized && Enums.MobilePanel.Groups === value) {
									$("#auroraContent").find('.MailLayout').find('.panel-left').prepend(oBannerEl);
									bInitialized = true;
								}
							});
						}
					});
				}
			};
		}
	}
	else
	{
		var
			fGetHeaderItem = function () {
				return {
					item: require('modules/%ModuleName%/js/views/HeaderItemView.js'),
					name: ''
				};
			},
			oResult = {}
		;

		switch (App.getUserRole()) {
			case Enums.UserRole.TenantAdmin:
				oResult.getHeaderItem = fGetHeaderItem;
				break;
			default:
				break;
		}

		return oResult;
	}

	return null;
};

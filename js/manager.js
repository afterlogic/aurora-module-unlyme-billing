'use strict'

module.exports = function (oAppData) {
	var
		App = require('%PathToCoreWebclientModule%/js/App.js')
	;

	if (!App.isMobile()) {

		if (App.getUserRole() === Enums.UserRole.TenantAdmin) {
			var
				TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
				Settings = require('modules/%ModuleName%/js/Settings.js')
			;
			Settings.init(oAppData);

			return {
				start: function (ModulesManager) {
					ModulesManager.run('SettingsWebclient', 'registerSettingsTab', [function () { return require('modules/%ModuleName%/js/views/SettingsPaneView.js'); }, Settings.HashModuleName, TextUtils.i18n('%MODULENAME%/LABEL_BILLING_SETTINGS_TAB')]);
				},
				getHeaderItem: function () {
					return {
						item: require('modules/%ModuleName%/js/views/HeaderItemView.js'),
						name: ''
					}
				},
			}
		}
	}

	return null
}

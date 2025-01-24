'use strict'

module.exports = function (oAppData) {
	var
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		Settings = require('modules/%ModuleName%/js/Settings.js')
	;

	Settings.init(oAppData)

	if (!App.isMobile()) {

		if (App.getUserRole() === Enums.UserRole.TenantAdmin) {
			return {
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

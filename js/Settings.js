'use strict';

var
	_ = require('underscore'),

	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js')
;

module.exports = {
	HashModuleName: 'unlyme-billing',
	ServerModuleName: '%ModuleName%',

	PaymentLink: '',

	/**
	 * Initializes settings from AppData object sections.
	 *
	 * @param {Object} oAppData Object contained modules settings.
	 */
	init: function (oAppData)
	{
		var oAppDataSection = oAppData[this.ServerModuleName];

		if (!_.isEmpty(oAppDataSection))
		{
			this.PaymentLink = Types.pString(oAppDataSection.PaymentLink, this.PaymentLink);
		}
	},
};

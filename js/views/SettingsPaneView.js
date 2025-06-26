'use strict';

var
	ko = require('knockout'),
	_ = require('underscore'),
	
	Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
	Api = require('%PathToCoreWebclientModule%/js/Api.js'),
	App = require('%PathToCoreWebclientModule%/js/App.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function CSettingsPaneView()
{
	this.subscriptions = ko.observableArray([]);
	this.cancelSubscriptionBind = _.bind(this.cancelSubscription, this);
}

CSettingsPaneView.prototype.ViewTemplate = '%ModuleName%_SettingsPaneView';

CSettingsPaneView.prototype.showTab = function ()
{
	Ajax.send(Settings.ServerModuleName, 'GetSubscriptionsInfo', {'TenantId': App.iTenantId}, this.onGetInfoResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CSettingsPaneView.prototype.onGetInfoResponse = function (oResponse, oRequest)
{
	var oResult = oResponse.Result;
	
	if (!oResult)
	{
		Api.showErrorByCode(oResponse);
	}
	else
	{
		if (_.isArray(oResult)) {
			_.each(oResult, function(resultItem) {
				resultItem.Status = ko.observable(resultItem.Status);
			});
		}
		this.subscriptions(oResult);
	}
};

CSettingsPaneView.prototype.cancelSubscription = function ()
{
	Ajax.send(Settings.ServerModuleName, 'CancelSubscription', {'TenantId': App.iTenantId}, this.onCancelSubscriptionResponse, this);
};

/**
 * @param {Object} oResponse
 * @param {Object} oRequest
 */
CSettingsPaneView.prototype.onCancelSubscriptionResponse = function (oResponse, oRequest)
{
	var oResult = oResponse.Result;
	if (!oResult)
	{
		Api.showErrorByCode(oResponse);
	}
	else
	{
		_.each(this.subscriptions(), function (subscription) {
			if (subscription.Id === oResult) {
				subscription.Status('canceled');
			}
		});
	}
};


module.exports = new CSettingsPaneView();

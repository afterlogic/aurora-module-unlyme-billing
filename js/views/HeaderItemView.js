'use strict';

var
	_ = require('underscore'),

	Settings = require('modules/%ModuleName%/js/Settings.js'),
	CAbstractHeaderItemView = require('%PathToCoreWebclientModule%/js/views/CHeaderItemView.js')
;

function CHeaderItemView()
{
	CAbstractHeaderItemView.call(this);

	this.sLink = Settings.PaymentLink;
}

_.extendOwn(CHeaderItemView.prototype, CAbstractHeaderItemView.prototype);

CHeaderItemView.prototype.ViewTemplate = '%ModuleName%_HeaderItemView';

module.exports = new CHeaderItemView();

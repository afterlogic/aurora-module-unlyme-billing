import AdminSettingsPerTenant from './components/AdminSettingsPerTenant'

export default {
  moduleName: 'BillingUnlyme',

  requiredModules: [],

  getAdminSystemTabs () {
    return [
      {
        tabName: 'reserved-list',
        tabTitle: 'BILLINGUNLYME.ADMIN_SETTINGS_RESERVED_LIST_TAB_LABEL',
        tabRouteChildren: [
          { path: 'reserved-list', component: () => import('./components/ReservedListAdminSettings') },
        ],
      },
    ]
  },

  getTenantOtherDataComponents () {
    return import('./components/EditTenant')
  },

  getAdminTenantTabs () {
    return [
      {
        tabName: 'billing-unlyme',
        tabTitle: 'BILLINGUNLYME.ADMIN_SETTINGS_TAB_LABEL',
        tabRouteChildren: [
          { path: 'id/:id/billing-unlyme', component: AdminSettingsPerTenant },
          { path: 'search/:search/id/:id/billing-unlyme', component: AdminSettingsPerTenant },
          { path: 'page/:page/id/:id/billing-unlyme', component: AdminSettingsPerTenant },
          { path: 'search/:search/page/:page/id/:id/billing-unlyme', component: AdminSettingsPerTenant },
        ],
      }
    ]
  },
}

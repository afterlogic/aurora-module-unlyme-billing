import AdminSettingsPerTenant from './components/AdminSettingsPerTenant'
import store from 'src/store'

export default {
  moduleName: 'UnlymeBilling',

  requiredModules: [],

  getAdminSystemTabs () {
    return [
      {
        tabName: 'reserved-list',
        tabTitle: 'UNLYMEBILLING.ADMIN_SETTINGS_RESERVED_LIST_TAB_LABEL',
        tabRouteChildren: [
          { path: 'reserved-list', component: () => import('./components/ReservedListAdminSettings') },
        ],
      },
    ]
  },

  getAdminTenantTabs () {
    const isUserSuperAdmin = store.getters['user/isUserSuperAdmin']
    if (isUserSuperAdmin) {
      return [
        {
          tabName: 'billing-unlyme',
          tabTitle: 'UNLYMEBILLING.ADMIN_SETTINGS_TAB_LABEL',
          tabRouteChildren: [
            { path: 'id/:id/billing-unlyme', component: AdminSettingsPerTenant },
            { path: 'search/:search/id/:id/billing-unlyme', component: AdminSettingsPerTenant },
            { path: 'page/:page/id/:id/billing-unlyme', component: AdminSettingsPerTenant },
            { path: 'search/:search/page/:page/id/:id/billing-unlyme', component: AdminSettingsPerTenant },
          ],
        }
      ]
    } else {
      return []
    }
  },
}

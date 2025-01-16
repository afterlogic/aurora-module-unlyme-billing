<template>
  <q-scroll-area class="full-height full-width">
    <div class="q-pa-lg ">
      <div class="row q-mb-md">
        <div class="col text-h5" v-t="'BILLINGUNLYME.HEADING_SETTINGS_TAB'"></div>
      </div>
      <q-card flat bordered class="card-edit-settings">
        <q-card-section>
          <div class="row q-mt-md">
            <div class="col-8">
              <q-checkbox dense v-model="isBusiness" :label="$t('BILLINGUNLYME.LABEL_ITS_BUSINESS_TENANT')" />
            </div>
          </div>
          <div class="row q-my-sm">
            <div class="col-8">
              <q-item-label caption>
                {{ $t('BILLINGUNLYME.HINT_ITS_BUSINESS_TENANT_HTML') }}
              </q-item-label>
            </div>
          </div>
          <div class="row">
            <div class="col-8">
              <q-checkbox dense v-model="isGroupwareEnabled" :label="$t('BILLINGUNLYME.LABEL_ENABLE_GROUPWARE')" />
            </div>
          </div>
        </q-card-section>
      </q-card>
      <div class="q-pa-md text-right">
        <q-btn unelevated no-caps dense class="q-px-sm" :ripple="false" color="primary"
               :label="$t('COREWEBCLIENT.ACTION_SAVE')"
               @click="save"/>
      </div>
    </div>
    <q-inner-loading style="justify-content: flex-start;" :showing="loading || saving">
      <q-linear-progress query />
    </q-inner-loading>
  </q-scroll-area>
</template>

<script>
import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import types from 'src/utils/types'
import webApi from 'src/utils/web-api'

const serverModuleName = 'BillingUnlyme'
export default {
  name: 'UnlimeAdminSettingsPerTenant',

  data () {
    return {
      saving: false,
      loading: false,
      tenant: null,

      isBusiness: false,
      isGroupwareEnabled: false,
    }
  },

  computed: {
    tenantId () {
      return this.$store.getters['tenants/getCurrentTenantId']
    }, 
  },

  watch: {
    '$store.state.tenants.tenants': {
      handler: function () {
        this.populate()
      },
      deep: true
    }
  },

  mounted () {
    this.loading = false
    this.saving = false
    this.populate()
  },

  beforeRouteLeave (to, from, next) {
    this.$root.doBeforeRouteLeave(to, from, next)
  },

  methods: {
    /**
     * Method is used in doBeforeRouteLeave mixin
     */
    hasChanges () {
      if (this.loading) {
        return false
      }

      const tenantCompleteData = types.pObject(this.tenant?.completeData)

      return this.isBusiness !== tenantCompleteData[`${serverModuleName}::IsBusiness`]
        || this.isGroupwareEnabled !== tenantCompleteData[`${serverModuleName}::isGroupwareEnabled`]
    },

    /**
     * Method is used in doBeforeRouteLeave mixin,
     * do not use async methods - just simple and plain reverting of values
     * !! hasChanges method must return true after executing revertChanges method
     */
    revertChanges () {
      this.populate()
    },

    populate () {
      const tenant = this.$store.getters['tenants/getTenant'](this.tenantId)

      console.log('tenant', this.tenant?.name, !!this.tenant, tenant.completeData[`${serverModuleName}::IsBusiness`], tenant.completeData[`${serverModuleName}::IsGroupwareEnabled`]);
      if (tenant) {
        if (tenant.completeData[`${serverModuleName}::IsBusiness`] !== undefined) {
          this.tenant = tenant
          this.isBusiness = tenant.completeData[`${serverModuleName}::IsBusiness`]
          this.isGroupwareEnabled = tenant.completeData[`${serverModuleName}::IsGroupwareEnabled`]
        } else {
          this.getSettings()
        }
      }
    },

    save () {
      if (!this.saving) {
        this.saving = true
        const parameters = {
          IsBusiness: this.isBusiness,
          IsGroupwareEnabled: this.isGroupwareEnabled,
          TenantId: this.tenantId
        }
        webApi.sendRequest({
          moduleName: serverModuleName,
          methodName: 'UpdateSettings',
          parameters,
        }).then(result => {
          this.saving = false
          if (result === true) {
            const data = {}
            data[`${serverModuleName}::IsBusiness`] = parameters.IsBusiness
            data[`${serverModuleName}::IsGroupwareEnabled`] = parameters.IsGroupwareEnabled

            this.$store.commit('tenants/setTenantCompleteData', { id: this.tenantId, data })
            notification.showReport(this.$t('COREWEBCLIENT.REPORT_SETTINGS_UPDATE_SUCCESS'))
          } else {
            notification.showError(this.$t('COREWEBCLIENT.ERROR_SAVING_SETTINGS_FAILED'))
          }
        }, response => {
          this.saving = false
          notification.showError(errors.getTextFromResponse(response, this.$t('COREWEBCLIENT.ERROR_SAVING_SETTINGS_FAILED')))
        })
      }
    },

    getSettings () {
      this.loading = true
      const parameters = {
        TenantId: this.tenantId
      }
      webApi.sendRequest({
        moduleName: serverModuleName,
        methodName: 'GetSettings',
        parameters,
      }).then(result => {
        this.loading = false
        if (result) {
          const data = {}
          data[`${serverModuleName}::IsBusiness`] = types.pBool(result.IsBusiness)
          data[`${serverModuleName}::IsGroupwareEnabled`] = types.pBool(result.IsGroupwareEnabled)
          
          this.$store.commit('tenants/setTenantCompleteData', { id: this.tenantId, data })
        }
      })
    },
  }
}
</script>

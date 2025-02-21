<template>
  <q-scroll-area class="full-height full-width">
    <div class="q-pa-lg">
      <div class="row q-mb-md">
        <div class="col text-h5" v-t="'UNLYMEBILLING.HEADING_SETTINGS_TAB'"></div>
      </div>
      <q-card flat bordered class="card-edit-settings">
        <q-card-section>
          <div class="row q-mb-md">
            <div class="col-2 q-mt-sm" v-t="'UNLYMEBILLING.LABEL_ADD_RESERVED_NAME'"></div>
            <div class="col-3">
              <q-input outlined dense bg-color="white" v-model="accountName" />
            </div>
            <div class="col-3 q-mt-xs q-ml-md">
              <q-btn
                unelevated
                no-caps
                no-wrap
                dense
                class="q-ml-md q-px-sm"
                :ripple="false"
                color="primary"
                :label="$t('UNLYMEBILLING.ACTION_ADD_NEW_RESERVED_NAME')"
                @click="save"
              />
            </div>
          </div>
          <div class="row q-mb-md">
            <div class="col-2" />
            <div class="col-4">
              <select size="9" class="select" multiple v-model="selectedNames">
                <option v-for="name in reservedList" :key="name" :value="name">{{ name }}</option>
              </select>
            </div>
            <div class="col-3 q-mt-xs q-ml-md" style="position: relative">
              <div style="position: absolute; bottom: 0">
                <q-btn
                  unelevated
                  no-caps
                  no-wrap
                  dense
                  class="q-ml-md q-px-sm"
                  :ripple="false"
                  color="primary"
                  :label="$t('UNLYMEBILLING.ACTION_DELETE_RESERVED_NAMES')"
                  @click="deleteReservedList"
                />
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
    <q-inner-loading style="justify-content: flex-start" :showing="loading || saving || deleting">
      <q-linear-progress query />
    </q-inner-loading>
  </q-scroll-area>
</template>

<script>
import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import types from 'src/utils/types'
import webApi from 'src/utils/web-api'

export default {
  name: 'GroupsLimitsAdminSettings',

  mounted() {
    this.populate()
  },

  data() {
    return {
      saving: false,
      deleting: false,
      loading: false,
      accountName: '',
      selectedNames: [],
      reservedList: [],
    }
  },

  beforeRouteLeave(to, from, next) {
    this.$root.doBeforeRouteLeave(to, from, next)
  },

  methods: {
    /**
     * Method is used in doBeforeRouteLeave mixin
     */
    hasChanges() {
      return this.accountName !== ''
    },

    /**
     * Method is used in doBeforeRouteLeave mixin,
     * do not use async methods - just simple and plain reverting of values
     * !! hasChanges method must return true after executing revertChanges method
     */
    revertChanges() {
      this.accountName = ''
    },

    populate() {
      this.getSettings()
    },
    save() {
      if (!this.saving) {
        if (this.accountName.length) {
          this.saving = true
          const parameters = {
            AccountName: this.accountName,
          }
          webApi
            .sendRequest({
              moduleName: 'UNLYMEBILLING',
              methodName: 'AddNewReservedName',
              parameters,
            })
            .then(
              (result) => {
                this.saving = false
                if (result === true) {
                  this.accountName = ''
                  this.populate()
                  notification.showReport(this.$t('COREWEBCLIENT.REPORT_SETTINGS_UPDATE_SUCCESS'))
                } else {
                  notification.showError(this.$t('COREWEBCLIENT.ERROR_SAVING_SETTINGS_FAILED'))
                }
              },
              (response) => {
                this.saving = false
                notification.showError(
                  errors.getTextFromResponse(response, this.$t('COREWEBCLIENT.ERROR_SAVING_SETTINGS_FAILED'))
                )
              }
            )
        } else {
          notification.showError(this.$t('UNLYMEBILLING.ERROR_EMPTY_RESERVED_NAME'))
        }
      }
    },
    deleteReservedList() {
      if (!this.deleting) {
        this.deleting = true
        if (this.selectedNames.length) {
          const parameters = {
            ReservedNames: this.selectedNames,
          }
          webApi
            .sendRequest({
              moduleName: 'UNLYMEBILLING',
              methodName: 'DeleteReservedNames',
              parameters,
            })
            .then(
              (result) => {
                this.deleting = false
                if (result === true) {
                  this.populate()
                }
              },
              (response) => {
                this.deleting = false
                notification.showError(errors.getTextFromResponse(response))
              }
            )
        } else {
          this.deleting = false
          notification.showError(this.$t('UNLYMEBILLING.ERROR_EMPTY_RESERVED_NAMES'))
        }
      }
    },
    getSettings() {
      this.loading = true
      webApi
        .sendRequest({
          moduleName: 'UNLYMEBILLING',
          methodName: 'GetReservedNames',
          Parameters: {},
        })
        .then(
          (result) => {
            this.loading = false
            if (result) {
              this.reservedList = types.pArray(result)
            }
          },
          (response) => {
            this.loading = false
            notification.showError(errors.getTextFromResponse(response))
          }
        )
    },
  },
}
</script>

<style scoped lang="scss">
.select {
  padding: 7px 9px 6px;
  border: 1px solid #cccccc;
  width: 100%;
  overflow-y: scroll;
  border-radius: 4px;
}
</style>

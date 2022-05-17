<!--
	- @copyright 2022 Carl Schwan <carl@carlschwan.eu>
	-
	- @author Carl Schwan <carl@carlschwan.eu>
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<SettingsSection :title="t('federatedfilesharing', 'Federated Cloud Sharing')"
		:description="t('federatedfilesharing', 'Adjust how people can share between servers. This includes shares between users on this server as well if they are using federated sharing.')"
		:doc-url="sharingFederatedDocUrl">
		<CheckboxRadioSwitch type="switch"
			:checked.sync="outgoingServer2serverShareEnabled"
			@update:checked="update('outgoing_server2server_share_enabled', outgoingServer2serverShareEnabled)">
			{{ t('federatedfilesharing', 'Allow users on this server to send shares to other servers (this option also allows WebDAV access to public shares)') }}
		</CheckboxRadioSwitch>

		<CheckboxRadioSwitch type="switch"
			:checked.sync="incomingServer2serverShareEnabled"
			@update:checked="update('incoming_server2server_share_enabled', incomingServer2serverShareEnabled)">
			{{ t('federatedfilesharing', 'Allow users on this server to receive shares from other servers') }}
		</CheckboxRadioSwitch>

		<CheckboxRadioSwitch type="switch"
			v-if="federatedGroupSharingSupported"
			:checked.sync="outgoingServer2serverGroupShareEnabled"
			@update:checked="update('outgoing_server2server_group_share_enabled', outgoingServer2serverGroupShareEnabled)">
			{{ t('federatedfilesharing', 'Allow users on this server to send shares to groups on other servers') }}
		</CheckboxRadioSwitch>

		<CheckboxRadioSwitch type="switch"
			v-if="federatedGroupSharingSupported"
			:checked.sync="incomingServer2serverGroupShareEnabled"
			@update:checked="update('incoming_server2server_group_share_enabled', incomingServer2serverGroupShareEnabled)">
			{{ t('federatedfilesharing', 'Allow users on this server to receive group shares from other servers') }}
		</CheckboxRadioSwitch>

		<CheckboxRadioSwitch type="switch"
			:checked.sync="lookupServerEnabled"
			@update:checked="update('lookupServerEnabled', lookupServerEnabled)">
			{{ t('federatedfilesharing', 'Search global and public address book for users') }}
		</CheckboxRadioSwitch>

		<CheckboxRadioSwitch type="switch"
			:checked.sync="lookupServerUploadEnabled"
			@update:checked="update('lookupServerUploadEnabled', lookupServerUploadEnabled)">
			{{ t('federatedfilesharing', 'Allow users to publish their data to a global and public address book') }}
		</CheckboxRadioSwitch>
	</SettingsSection>
</template>

<script>
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import confirmPassword from '@nextcloud/password-confirmation'

const outgoingServer2serverShareEnabled = loadState('federatedfilesharing', 'outgoingServer2serverShareEnabled')
const incomingServer2serverShareEnabled = loadState('federatedfilesharing', 'incomingServer2serverShareEnabled')
const outgoingServer2serverGroupShareEnabled = loadState('federatedfilesharing', 'outgoingServer2serverGroupShareEnabled')
const incomingServer2serverGroupShareEnabled = loadState('federatedfilesharing', 'incomingServer2serverGroupShareEnabled')
const federatedGroupSharingSupported = loadState('federatedfilesharing', 'federatedGroupSharingSupported')
const lookupServerEnabled = loadState('federatedfilesharing', 'lookupServerEnabled')
const lookupServerUploadEnabled = loadState('federatedfilesharing', 'lookupServerUploadEnabled')
const internalOnly = loadState('federatedfilesharing', 'internalOnly')
const sharingFederatedDocUrl = loadState('federatedfilesharing', 'sharingFederatedDocUrl')

export default {
	name: 'AdminSettings',

	components: {
		CheckboxRadioSwitch,
		SettingsSection,
	},

	data() {
		return {
			outgoingServer2serverShareEnabled,
			incomingServer2serverShareEnabled,
			outgoingServer2serverGroupShareEnabled,
			incomingServer2serverGroupShareEnabled,
			federatedGroupSharingSupported,
			lookupServerEnabled,
			lookupServerUploadEnabled,
			internalOnly,
			sharingFederatedDocUrl,
		}
	},
	methods: {
		async update(key, value) {
			await confirmPassword()

			const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
				appId: 'files_sharing',
				key,
			})

			const stringValue = value ? 'yes' : 'no'
			try {
				const { data } = await axios.post(url, {
					value: stringValue,
				})
				this.handleResponse({
					status: data.ocs?.meta?.status
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('federatedfilesharing', 'Unable to update federated files sharing config'),
					error: e,
				})
			}
		},
		async handleResponse({ status, errorMessage, error }) {
			if (status !== 'ok') {
				showError(errorMessage)
				console.error(errorMessage, error)
			}
		},
	}
}
</script>

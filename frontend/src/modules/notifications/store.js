import { ref } from "vue";
import { defineStore } from "pinia";
import { notificationApi } from "./api.js";

export const useNotificationsStore = defineStore("notifications", () => {
	const notifications = ref([]);
	const unreadNotifications = ref(null);
	const pagination = ref(null);
	const preferences = ref(null);
	const pending = ref(false);
	const error = ref("");
	let generation = 0;
	let unreadRequest = 0;
	let operations = 0;

	async function run(action) {
		const current = generation;
		operations++;
		pending.value = true;
		error.value = "";
		try {
			return await action();
		} catch (failure) {
			if (current === generation) {
				error.value =
					failure.response?.data?.message ||
					"Falha ao atualizar notificações.";
			}
			throw failure;
		} finally {
			if (current === generation) pending.value = --operations > 0;
		}
	}

	async function refreshUnreadNotifications() {
		const g = generation,
			request = ++unreadRequest;
		try {
			const { data } = await notificationApi.unreadCount();
			if (g === generation && request === unreadRequest)
				unreadNotifications.value = data.unread_count;
		} catch {
			if (g === generation && request === unreadRequest)
				unreadNotifications.value = null;
		}
	}
	async function loadNotifications(page = 1) {
		const g = generation;
		const { data } = await run(() => notificationApi.notifications(page));
		if (g !== generation) return;
		notifications.value = data.data;
		pagination.value = { current: data.current_page, last: data.last_page };
		await refreshUnreadNotifications();
	}
	async function read(id) {
		const g = generation;
		await run(() => notificationApi.read(id));
		if (g !== generation) return;
		const n = notifications.value.find((n) => n.id === id);
		if (n) n.read_at = new Date().toISOString();
		await refreshUnreadNotifications();
	}

	async function loadPreferences() {
		const current = generation;
		const { data } = await run(notificationApi.preferences);
		if (current === generation) preferences.value = data;
	}

	async function savePreferences(settings) {
		const current = generation;
		const { data } = await run(() =>
			notificationApi.savePreferences(settings),
		);
		if (current === generation) preferences.value = data;
	}

	function clear() {
		generation++;
		unreadRequest++;
		operations = 0;
		notifications.value = [];
		unreadNotifications.value = null;
		pagination.value = null;
		preferences.value = null;
		pending.value = false;
		error.value = "";
	}

	return {
		notifications,
		unreadNotifications,
		pagination,
		preferences,
		pending,
		error,
		refreshUnreadNotifications,
		loadNotifications,
		read,
		loadPreferences,
		savePreferences,
		clear,
	};
});

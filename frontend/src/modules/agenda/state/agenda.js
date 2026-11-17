import { defineStore } from "pinia";
import { ref } from "vue";
import { createContextRequests } from "@/state/context-requests.js";
import { agendaApi } from "../api.js";

export const useAgendaStore = defineStore("agenda", () => {
	const { error, pending, version, run, resetRequests } =
		createContextRequests();
	const agenda = ref([]);
	const agendaPage = ref(null);
	let requestNumber = 0;

	async function loadAgenda(filters = {}, { allPages = false } = {}) {
		const g = version(),
			request = ++requestNumber;
		const current = () => g === version() && request === requestNumber;
		agenda.value = [];
		agendaPage.value = null;
		await run(async () => {
			let { data } = await agendaApi.agenda(filters);
			const first = data;
			const rows = [...data.data];
			while (allPages && data.current_page < data.last_page) {
				if (!current()) return;
				const response = await agendaApi.agenda({
					...filters,
					page: data.current_page + 1,
				});
				data = response.data;
				rows.push(...data.data);
			}
			if (current()) {
				agenda.value = [
					...new Map(rows.map((row) => [row.id, row])).values(),
				];
				agendaPage.value = { ...first, data: agenda.value };
			}
		}, current);
	}
	function clear() {
		resetRequests();
		agenda.value = [];
		agendaPage.value = null;
	}

	return {
		agenda,
		agendaPage,
		error,
		pending,
		loadAgenda,
		clear,
	};
});

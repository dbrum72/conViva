<template>
  <section class="agenda-month" aria-label="Calendário mensal">
    <div class="agenda-weekdays" aria-hidden="true">
      <span v-for="day in weekdays" :key="day">{{ day }}</span>
    </div>
    <div ref="grid" class="agenda-month-grid">
      <button
        v-for="date in dates"
        :key="date"
        type="button"
        class="agenda-date"
        :class="{
          'is-outside': date.slice(0, 7) !== month.slice(0, 7),
          'is-today': date === today,
          'is-selected': date === selectedDate,
        }"
        :data-date="date"
        :aria-label="`${dateLabel(date, { dateStyle: 'full' })}, ${items[date]?.length || 0} registros`"
        :aria-pressed="date === selectedDate"
        :aria-current="date === today ? 'date' : undefined"
        :tabindex="date === focusDate ? 0 : -1"
        @click="$emit('select', date)"
        @keydown="navigate($event, date)"
      >
        <span class="agenda-date-heading"
          ><span class="agenda-date-number">{{ Number(date.slice(-2)) }}</span
          ><span v-if="items[date]?.length" class="agenda-date-count">{{
            items[date].length
          }}</span></span
        >
        <span class="agenda-date-items"
          ><span
            v-for="item in (items[date] || []).slice(0, 3)"
            :key="item.id"
            class="agenda-preview"
            :class="[
              `agenda-kind-${item.kind}`,
              { 'agenda-is-overdue': item.is_overdue },
            ]"
            ><i aria-hidden="true" /><span class="agenda-preview-time">{{
              item.kind === "unavailability"
                ? "Dia inteiro"
                : timeLabel(item.due_at, timezone)
            }}</span
            ><span class="agenda-preview-title">{{ item.title }}</span></span
          ><span v-if="items[date]?.length > 3" class="agenda-more"
            >+{{ items[date].length - 3 }} registros</span
          ></span
        >
      </button>
    </div>
  </section>
</template>
<script setup>
import { computed, nextTick, ref } from "vue";
import { addDays, dateLabel, timeLabel, weekdays } from "@/utils/agenda";
const props = defineProps({
  dates: Array,
  items: Object,
  month: String,
  today: String,
  selectedDate: String,
  timezone: String,
});
const emit = defineEmits(["select"]);
const grid = ref(null);
const focusDate = computed(() =>
  props.dates.includes(props.selectedDate)
    ? props.selectedDate
    : props.dates[0],
);
async function navigate(event, date) {
  const offsets = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
  if (!(event.key in offsets)) return;
  const next = addDays(date, offsets[event.key]);
  if (!props.dates.includes(next)) return;
  event.preventDefault();
  emit("select", next);
  await nextTick();
  grid.value?.querySelector(`[data-date="${next}"]`)?.focus();
}
</script>

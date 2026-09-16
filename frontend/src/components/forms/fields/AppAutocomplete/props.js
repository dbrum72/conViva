import { fieldProps } from "@/components/forms/shared/props/field.js";
import { controlProps } from "@/components/forms/shared/props/control.js";

export const appAutocompleteProps = {
  ...fieldProps,
  ...controlProps,

  modelValue: {
    type: [String, Number, Boolean, Object, null],
    default: null,
  },

  searchValue: {
    type: String,
    default: "",
  },

  placeholder: {
    type: String,
    default: "",
  },

  autocomplete: {
    type: String,
    default: "off",
  },

  options: {
    type: Array,
    default: () => [],
  },

  optionLabel: {
    type: String,
    default: "label",
  },

  optionValue: {
    type: String,
    default: "value",
  },

  noResultsText: {
    type: String,
    default: "Nenhum resultado encontrado.",
  },

  minSearchLength: {
    type: Number,
    default: 0,
    validator: (value) => Number.isInteger(value) && value >= 0,
  },

  openOnFocus: {
    type: Boolean,
    default: true,
  },

  clearable: {
    type: Boolean,
    default: false,
  },
};

export const autocompleteControlProps = {
  modelValue: {
    type: [String, Number, Boolean, Object, null],
    default: null,
  },

  searchValue: {
    type: String,
    default: "",
  },

  id: {
    type: String,
    default: undefined,
  },

  name: {
    type: String,
    default: undefined,
  },

  placeholder: {
    type: String,
    default: "",
  },

  disabled: {
    type: Boolean,
    default: false,
  },

  required: {
    type: Boolean,
    default: false,
  },

  autofocus: {
    type: Boolean,
    default: false,
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

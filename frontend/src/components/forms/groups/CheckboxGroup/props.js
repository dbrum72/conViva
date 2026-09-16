import { fieldProps } from "@/components/forms/shared/props/field.js";

const { readonly, ...checkboxGroupFieldProps } = fieldProps;

export const checkboxGroupProps = {
  ...checkboxGroupFieldProps,

  modelValue: {
    type: Array,
    default: () => [],
  },

  name: {
    type: String,
    required: true,
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

  optionDisabled: {
    type: String,
    default: "disabled",
  },

  orientation: {
    type: String,
    default: "vertical",
    validator: (value) => ["vertical", "horizontal"].includes(value),
  },
};

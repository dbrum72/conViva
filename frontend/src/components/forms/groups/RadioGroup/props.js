import { fieldProps } from "@/components/forms/shared/props/field.js";

const { readonly, ...radioGroupFieldProps } = fieldProps;

export const radioGroupProps = {
  ...radioGroupFieldProps,

  modelValue: {
    type: [String, Number, Boolean, null],
    default: null,
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

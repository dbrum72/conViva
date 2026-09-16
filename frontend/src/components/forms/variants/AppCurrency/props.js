import { appInputProps } from "@/components/forms/fields/AppInput/props.js";

export const appCurrencyProps = {
  ...appInputProps,

  modelValue: {
    type: Number,
    default: null,
  },

  locale: {
    type: String,
    default: "pt-BR",
  },

  currency: {
    type: String,
    default: "BRL",
  },

  precision: {
    type: Number,
    default: 2,
    validator: (value) => Number.isInteger(value) && value >= 0 && value <= 20,
  },

  min: {
    type: Number,
    default: undefined,
  },

  max: {
    type: Number,
    default: undefined,
  },

  allowNegative: {
    type: Boolean,
    default: false,
  },

  allowEmpty: {
    type: Boolean,
    default: true,
  },

  useGrouping: {
    type: Boolean,
    default: true,
  },

  showCurrency: {
    type: Boolean,
    default: true,
  },

  shiftDecimal: {
    type: Boolean,
    default: false,
  },
};

import { appInputProps } from "@/components/forms/fields/AppInput/props.js";

export const appDateProps = {
  ...appInputProps,

  modelValue: {
    type: String,
    default: "",
  },

  type: {
    type: String,
    default: "date",
  },

  autocomplete: {
    type: String,
    default: "off",
  },

  min: {
    type: String,
    default: undefined,
  },

  max: {
    type: String,
    default: undefined,
  },

  step: {
    type: [Number, String],
    default: 1,
  },

  showIcon: {
    type: Boolean,
    default: true,
  },
};

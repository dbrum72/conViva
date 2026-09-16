import { appInputProps } from "@/components/forms/fields/AppInput/props.js";

export const appNumberProps = {
  ...appInputProps,

  modelValue: {
    type: Number,
    default: null,
  },

  type: {
    type: String,
    default: "number",
  },

  inputmode: {
    type: String,
    default: "decimal",
  },

  min: {
    type: Number,
    default: undefined,
  },

  max: {
    type: Number,
    default: undefined,
  },

  step: {
    type: [Number, String],
    default: 1,
  },

  allowEmpty: {
    type: Boolean,
    default: true,
  },

  showIcon: {
    type: Boolean,
    default: false,
  },
};

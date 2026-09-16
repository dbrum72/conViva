import { appInputProps } from "@/components/forms/fields/AppInput/props.js";

export const appPhoneProps = {
  ...appInputProps,

  type: {
    type: String,
    default: "tel",
  },

  autocomplete: {
    type: String,
    default: "tel",
  },

  inputmode: {
    type: String,
    default: "tel",
  },

  showIcon: {
    type: Boolean,
    default: true,
  },
};

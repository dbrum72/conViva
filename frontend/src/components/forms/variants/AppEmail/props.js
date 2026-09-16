import { appInputProps } from "@/components/forms/fields/AppInput/props.js";

export const appEmailProps = {
  ...appInputProps,

  type: {
    type: String,
    default: "email",
  },

  autocomplete: {
    type: String,
    default: "email",
  },

  inputmode: {
    type: String,
    default: "email",
  },

  showIcon: {
    type: Boolean,
    default: true,
  },
};

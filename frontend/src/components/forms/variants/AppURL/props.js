import { appInputProps } from "@/components/forms/fields/AppInput/props.js";

export const appUrlProps = {
  ...appInputProps,

  type: {
    type: String,
    default: "url",
  },

  autocomplete: {
    type: String,
    default: "url",
  },

  inputmode: {
    type: String,
    default: "url",
  },

  showIcon: {
    type: Boolean,
    default: true,
  },
};

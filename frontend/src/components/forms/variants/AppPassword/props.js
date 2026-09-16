import { appInputProps } from "@/components/forms/fields/AppInput/props.js";

export const appPasswordProps = {
  ...appInputProps,

  showToggle: {
    type: Boolean,
    default: true,
  },

  visibleLabel: {
    type: String,
    default: "Ocultar senha",
  },

  hiddenLabel: {
    type: String,
    default: "Mostrar senha",
  },
};

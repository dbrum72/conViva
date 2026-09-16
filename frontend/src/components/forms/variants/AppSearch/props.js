import { appInputProps } from "@/components/forms/fields/AppInput/props.js";

export const appSearchProps = {
  ...appInputProps,

  clearable: {
    type: Boolean,
    default: true,
  },

  clearLabel: {
    type: String,
    default: "Limpar pesquisa",
  },

  searchLabel: {
    type: String,
    default: "Pesquisar",
  },
};

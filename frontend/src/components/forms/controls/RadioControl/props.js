import { controlProps } from "@/components/forms/shared/props/control.js";

export const radioControlProps = {
  ...controlProps,

  modelValue: {
    type: [String, Number, Boolean, null],
    default: null,
  },

  value: {
    type: [String, Number, Boolean],
    required: true,
  },

  ariaInvalid: {
    type: [Boolean, String],
    default: undefined,
  },

  ariaDescribedBy: {
    type: String,
    default: undefined,
  },
};

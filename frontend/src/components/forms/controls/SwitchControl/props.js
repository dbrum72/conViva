import { controlProps } from "@/components/forms/shared/props/control.js";

export const switchControlProps = {
  ...controlProps,

  modelValue: {
    type: Boolean,
    default: false,
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

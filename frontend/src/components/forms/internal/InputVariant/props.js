export const inputVariantProps = {
  modelValue: {
    type: [String, Number],
    default: "",
  },

  config: {
    type: Object,
    default: () => ({}),
  },

  inputProps: {
    type: Object,
    default: () => ({}),
  },
};

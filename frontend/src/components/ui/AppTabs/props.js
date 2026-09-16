export const appTabsProps = {
  modelValue: {
    type: [String, Number],

    required: true,
  },

  items: {
    type: Array,

    default: () => [],
  },

  ariaLabel: {
    type: String,

    default: "Navegação por seções",
  },
};

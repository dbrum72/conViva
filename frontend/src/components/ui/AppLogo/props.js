export const appLogoProps = {
  text: {
    type: String,
    default: "conViva",
  },

  to: {
    type: [String, Object],
    default: () => ({
      name: "dashboard",
    }),
  },

  ariaLabel: {
    type: String,
    default: "conViva — ir para o início",
  },
};

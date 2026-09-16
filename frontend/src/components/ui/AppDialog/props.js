export const appDialogProps = {
  busy: { type: Boolean, default: false },
  error: { type: String, default: "" },
  open: {
    type: Boolean,
    default: false,
  },

  title: {
    type: String,
    required: true,
  },

  size: {
    type: String,
    default: "md",
    validator: (value) => ["sm", "md", "lg"].includes(value),
  },

  closeOnBackdrop: {
    type: Boolean,
    default: true,
  },

  closeOnEscape: {
    type: Boolean,
    default: true,
  },
};

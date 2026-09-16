export const sideBarItemProps = {
  item: {
    type: Object,
    required: true,
    validator: (value) =>
      Boolean(
        value &&
        typeof value.name === "string" &&
        typeof value.label === "string",
      ),
  },
};

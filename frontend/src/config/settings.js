export const settingsPermissions = ["roles.view", "organization-members.view"];
export function permits(auth, rule) {
  return (
    (!rule.permission || auth.hasPermission(rule.permission)) &&
    (!rule.permissionsAny ||
      rule.permissionsAny.some((p) => auth.hasPermission(p)))
  );
}

const TENANT_KEY = "conviva.current_tenant";

export function getCurrentTenant() {
  return sessionStorage.getItem(TENANT_KEY);
}

export function setCurrentTenant(tenant) {
  if (!tenant) {
    removeCurrentTenant();
    return;
  }

  sessionStorage.setItem(TENANT_KEY, String(tenant));
}

export function removeCurrentTenant() {
  sessionStorage.removeItem(TENANT_KEY);
}

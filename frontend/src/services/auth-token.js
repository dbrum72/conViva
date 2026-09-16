const TOKEN_KEY = "conviva.access_token";

export function getAccessToken() {
  return sessionStorage.getItem(TOKEN_KEY);
}

export function setAccessToken(token) {
  if (!token) {
    removeAccessToken();
    return;
  }

  sessionStorage.setItem(TOKEN_KEY, token);
}

export function removeAccessToken() {
  sessionStorage.removeItem(TOKEN_KEY);
}

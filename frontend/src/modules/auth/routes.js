export const authRoutes = [
  {
    path: "/forgot-password",
    name: "password.forgot",
    component: () => import("./pages/PasswordRecoveryPage.vue"),
  },
  {
    path: "/reset-password",
    name: "password.reset",
    component: () => import("./pages/PasswordRecoveryPage.vue"),
  },
  {
    path: "/login",
    name: "login",
    component: () => import("./pages/LoginPage.vue"),
    meta: { guestOnly: true },
  },
  {
    path: "/register",
    name: "register",
    component: () => import("./pages/RegisterPage.vue"),
    meta: { guestOnly: true },
  },
];

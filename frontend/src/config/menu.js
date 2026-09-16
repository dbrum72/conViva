export default [
  {
    id: "my-recipients",
    label: "Meus assistidos",
    name: "organizations.select",
    icon: "users",
  },
  {
    id: "dashboard",
    label: "Visão geral",
    name: "dashboard",
    icon: "dashboard",
  },
  { id: "recipients", label: "Assistido", name: "recipients", icon: "users" },
  {
    id: "agenda",
    label: "Agenda de cuidados",
    name: "agenda",
    icon: "calendar",
  },
  { id: "finance", label: "Despesas", name: "finance", icon: "wallet" },
  {
    id: "organization-members",
    label: "Grupo e cuidadores",
    name: "organization-members",
    icon: "user",
    permission: "organization-members.view",
  },
  {
    id: "notifications",
    label: "Notificações",
    name: "notifications",
    icon: "email",
  },
  {
    id: "settings",
    label: "Permissões",
    name: "settings",
    icon: "settings",
    permission: "roles.view",
  },
];

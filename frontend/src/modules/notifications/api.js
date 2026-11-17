import { authenticatedClient as client } from "@/modules/http";

export const notificationApi = {
	unreadCount: () => client.get("/notifications/unread-count"),
	notifications: (page) => client.get("/notifications", { params: { page } }),
	read: (id) => client.post("/notifications/" + id + "/read"),
	preferences: () => client.get("/notifications/preferences"),
	savePreferences: (settings) =>
		client.put("/notifications/preferences", settings),
};

self.addEventListener("push", function (event) {
    if (!(self.Notification && self.Notification.permission === "granted")) {
        return;
    }

    const data = event.data ? event.data.json() : {};
    const title = data.title || "東大ラーメンログ";
    const message = data.body || "新しいお知らせがあります";
    const icon = "/images/icon-192.png"; // アイコン画像のパス
    const badge = "/images/icon-192.png"; // Androidのステータスバー用（小さいアイコン推奨）
    const link = data.action || "/"; // クリック時の飛び先

    const options = {
        body: message,
        icon: icon,
        badge: badge,
        data: {
            url: link,
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", function (event) {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});

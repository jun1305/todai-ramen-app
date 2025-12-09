self.addEventListener("install", function (event) {
    console.log("Service Worker installing.");
});

self.addEventListener("activate", function (event) {
    console.log("Service Worker activating.");
});

self.addEventListener("fetch", function (event) {
    // ここにキャッシュ戦略などを書きますが、
    // まずはアプリ化させるだけなら「何もしない」でOKです。
    // これがないとPWAとして認識されないことがあります。
});

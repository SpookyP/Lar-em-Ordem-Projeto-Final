  import Echo from "laravel-echo";
  import Pusher from "pusher-js";
  import axiosClient from "./api/axiosClient";

  window.Pusher = Pusher;

  export const echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
    // the API is on another origin and uses the Sanctum session, so authorize through axiosClient
    authorizer: (channel) => ({
      authorize: (socketId, callback) => {
        axiosClient
          .post("/api/broadcasting/auth", { socket_id: socketId, channel_name: channel.name })
          .then((response) => callback(null, response.data))
          .catch((error) => callback(error, null));
      },
    }),
  });
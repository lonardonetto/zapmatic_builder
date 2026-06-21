const TARGET_URL =
  "http://zapmatic.tec.br/api/send_pedido?instance_id=69FB46BE69E37&access_token=68eff9785d556";

export default {
  async fetch(request) {
    if (request.method !== "POST") {
      return new Response(
        JSON.stringify({
          status: "error",
          message: "Only POST is allowed",
        }),
        {
          status: 405,
          headers: { "content-type": "application/json; charset=UTF-8" },
        }
      );
    }

    const incomingHeaders = new Headers(request.headers);
    incomingHeaders.delete("host");
    incomingHeaders.delete("content-length");

    incomingHeaders.set("x-relay-source", "cloudflare-worker");

    const upstreamResponse = await fetch(TARGET_URL, {
      method: "POST",
      headers: incomingHeaders,
      body: request.body,
      redirect: "follow",
    });

    return new Response(upstreamResponse.body, {
      status: upstreamResponse.status,
      headers: {
        "content-type":
          upstreamResponse.headers.get("content-type") ||
          "application/json; charset=UTF-8",
      },
    });
  },
};

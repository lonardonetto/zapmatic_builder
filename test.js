const url = new URL("https://zapmatic.tec.br/mcp/oauth/callback");
console.log(url.hostname, url.port ? parseInt(url.port, 10) : (url.protocol === "https:" ? 443 : 80));

module.exports = {
  apps: [
    {
      name: "pluszap-bot-worker-all",
      script: "spark",
      args: "bot:all",
      interpreter: "php",
      cwd: "/www/wwwroot/app_zapmatic_app",
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      watch: false,
      max_memory_restart: "256M",
      error_file: "writable/logs/pm2-all-error.log",
      out_file: "writable/logs/pm2-all-out.log",
    },
    {
      name: "call-campaign-worker",
      script: "spark",
      args: "call:campaigns",
      interpreter: "php",
      cwd: "/www/wwwroot/app_zapmatic_app",
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      watch: false,
      max_memory_restart: "128M",
      error_file: "writable/logs/pm2-call-error.log",
      out_file: "writable/logs/pm2-call-out.log",
    },
  ]
};

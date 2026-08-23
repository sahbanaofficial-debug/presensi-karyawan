import { defineRailway, mysql, preserve, project, service, volume } from "railway/iac";

export default defineRailway(() => {
  const MySQL = mysql("MySQL", { region: "ams" });
  MySQL.deploy = { startCommand: "docker-entrypoint.sh mysqld --innodb-use-native-aio=0 --disable-log-bin --performance_schema=0 --innodb-buffer-pool-size=1G" };
  const mysqlVolume = volume("mysql-volume", { alerts: { usage: { "100": {}, "80": {}, "95": {} } }, allowOnlineResize: true, region: "ams", sizeMB: 500 });
  const presensiWeb = service("presensi-web", {
    build: "pnpm run build",
    healthcheck: "/up",
    healthcheckTimeout: 300,
    preDeploy: "chmod +x ./railway/init-app.sh && sh ./railway/init-app.sh",
    replicas: { "ams": 1 },
    env: {
      APP_DEBUG: preserve(),
      APP_ENV: preserve(),
      APP_FAKER_LOCALE: preserve(),
      APP_FALLBACK_LOCALE: preserve(),
      APP_KEY: preserve(),
      APP_LOCALE: preserve(),
      APP_MAINTENANCE_DRIVER: preserve(),
      APP_NAME: preserve(),
      APP_URL: preserve(),
      BCRYPT_ROUNDS: preserve(),
      CACHE_STORE: preserve(),
      DB_CONNECTION: preserve(),
      DB_URL: preserve(),
      FILESYSTEM_DISK: preserve(),
      LOG_CHANNEL: preserve(),
      LOG_LEVEL: preserve(),
      LOG_STDERR_FORMATTER: preserve(),
      PRESENSI_INITIAL_PASSWORD: preserve(),
      QUEUE_CONNECTION: preserve(),
      SESSION_DRIVER: preserve(),
      SESSION_SAME_SITE: preserve(),
      SESSION_SECURE_COOKIE: preserve(),
    },
  });
  const presensiCron = service("presensi-cron", {
    build: "pnpm run build",
    start: "chmod +x ./railway/run-cron.sh && sh ./railway/run-cron.sh",
    replicas: { "ams": 1 },
    env: {
      APP_DEBUG: presensiWeb.env.APP_DEBUG,
      APP_ENV: presensiWeb.env.APP_ENV,
      APP_FAKER_LOCALE: presensiWeb.env.APP_FAKER_LOCALE,
      APP_FALLBACK_LOCALE: presensiWeb.env.APP_FALLBACK_LOCALE,
      APP_KEY: presensiWeb.env.APP_KEY,
      APP_LOCALE: presensiWeb.env.APP_LOCALE,
      APP_MAINTENANCE_DRIVER: presensiWeb.env.APP_MAINTENANCE_DRIVER,
      APP_NAME: presensiWeb.env.APP_NAME,
      APP_URL: presensiWeb.env.APP_URL,
      BCRYPT_ROUNDS: presensiWeb.env.BCRYPT_ROUNDS,
      CACHE_STORE: presensiWeb.env.CACHE_STORE,
      DB_CONNECTION: presensiWeb.env.DB_CONNECTION,
      DB_URL: MySQL.env.MYSQL_URL,
      FILESYSTEM_DISK: presensiWeb.env.FILESYSTEM_DISK,
      LOG_CHANNEL: presensiWeb.env.LOG_CHANNEL,
      LOG_LEVEL: presensiWeb.env.LOG_LEVEL,
      LOG_STDERR_FORMATTER: presensiWeb.env.LOG_STDERR_FORMATTER,
      QUEUE_CONNECTION: presensiWeb.env.QUEUE_CONNECTION,
      SESSION_DRIVER: presensiWeb.env.SESSION_DRIVER,
      SESSION_SAME_SITE: presensiWeb.env.SESSION_SAME_SITE,
      SESSION_SECURE_COOKIE: presensiWeb.env.SESSION_SECURE_COOKIE,
    },
  });

  return project("presensi-karyawan-sahbana", {
    resources: [MySQL, presensiWeb, presensiCron, mysqlVolume],
  });
});

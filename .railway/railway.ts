import { defineRailway, mysql, preserve, project, service, volume } from "railway/iac";

export default defineRailway(() => {
  const MySQL = mysql("MySQL", { region: "ams" });
  MySQL.deploy = { startCommand: "docker-entrypoint.sh mysqld --innodb-use-native-aio=0 --disable-log-bin --performance_schema=0 --innodb-buffer-pool-size=1G" };
  const mysqlVolume = volume("mysql-volume", { alerts: { usage: { "100": {}, "80": {}, "95": {} } }, allowOnlineResize: true, region: "ams", sizeMB: 500 });
  const presensiWeb = service("presensi-web", {
    build: "pnpm run build",
    healthcheck: "/up",
    healthcheckTimeout: 300,
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

  return project("presensi-karyawan-sahbana", {
    resources: [MySQL, presensiWeb, mysqlVolume],
  });
});

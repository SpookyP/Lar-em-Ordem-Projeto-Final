const LOCALE = "pt-PT";
const rtf = new Intl.RelativeTimeFormat(LOCALE, { numeric: "auto" });

export function formatDate(iso) {
  if (!iso) return "";
  return new Date(iso).toLocaleDateString(LOCALE);
}

export function formatRelative(iso) {
  if (!iso) return "";

  const minutes = Math.min(0, Math.trunc((new Date(iso) - Date.now()) / 60000));
  const abs = Math.abs(minutes);

  if (abs < 1) return "agora mesmo";
  if (abs < 60) return rtf.format(minutes, "minute");
  if (abs < 60 * 24) return rtf.format(Math.trunc(minutes / 60), "hour");
  if (abs < 60 * 24 * 30) return rtf.format(Math.trunc(minutes / (60 * 24)), "day");
  return formatDate(iso);
}
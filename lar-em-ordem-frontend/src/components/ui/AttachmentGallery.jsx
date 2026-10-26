//so aceita http(s), evita hrefs com "javascript:" ou "data:"
function safeUrl(value) {
  try {
    const url = new URL(value);
    return url.protocol === "http:" || url.protocol === "https:"
      ? url.href
      : null;
  } catch {
    return null;
  }
}

export function AttachmentGallery({ attachments }) {
  const photos = attachments.filter((a) => safeUrl(a.url));
  if (photos.length === 0) return null;

  return (
    <div className="flex flex-col gap-2 border-t border-surface pt-4">
      <h3 className="text-sm font-semibold text-muted">Fotografias</h3>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        {photos.map((a) => (
          <a
            key={a.id}
            href={safeUrl(a.url)}
            target="_blank"
            rel="noopener noreferrer"
          >
            <img
              src={safeUrl(a.url)}
              alt="Fotografia da avaria"
              loading="lazy"
              referrerPolicy="no-referrer"
              className="aspect-square w-full rounded-xl object-cover"
            />
          </a>
        ))}
      </div>
    </div>
  );
}
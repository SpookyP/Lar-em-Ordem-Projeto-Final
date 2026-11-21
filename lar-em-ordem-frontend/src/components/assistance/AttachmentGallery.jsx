import { useEffect, useState } from "react";
import { getAttachmentUrl } from "../../services/api/assistanceService";

export function AttachmentPhoto({ id }) {
  const [src, setSrc] = useState(null);

  useEffect(() => {
    let active = true;
    let url = null;

    getAttachmentUrl(id)
      .then((result) => {
        url = result;
        if (active) setSrc(result);
      })
      .catch(() => {});

    // frees the photo from memory when the gallery closes
    return () => {
      active = false;
      if (url) URL.revokeObjectURL(url);
    };
  }, [id]);

  if (!src) {
    return <div className="aspect-square w-full animate-pulse rounded-xl bg-surface" />;
  }

  return (
    <a href={src} target="_blank" rel="noopener noreferrer">
      <img
        src={src}
        alt="Fotografia da avaria"
        className="aspect-square w-full rounded-xl object-cover"
      />
    </a>
  );
}

export function AttachmentGallery({ attachments }) {
  if (attachments.length === 0) return null;

  return (
    <div className="flex flex-col gap-2 border-t border-surface pt-4">
      <h3 className="text-sm font-semibold text-muted">Fotografias</h3>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        {attachments.map((a) => (
          <AttachmentPhoto key={a.id} id={a.id} />
        ))}
      </div>
    </div>
  );
}
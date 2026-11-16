import { Link } from "react-router-dom";
import { SITE_PAGES } from "../../data/siteInfo";

export default function SitePage({ page }) {
  const { title, intro, sections } = SITE_PAGES[page];

  return (
    <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
      <header className="flex flex-col gap-1">
        <h1 className="font-display text-3xl text-navy">{title}</h1>
        <p className="text-sm text-muted">{intro}</p>
      </header>

      <div className="grid gap-6 md:grid-cols-2">
        {sections.map(({ icon: Icon, title: sectionTitle, body, list, link }) => (
          <section
            key={sectionTitle}
            className="flex flex-col gap-3 rounded-2xl border border-border bg-card p-6"
          >
            <div className="flex items-center gap-3">
              <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-navy/10 text-navy">
                <Icon size={18} aria-hidden="true" />
              </span>
              <h2 className="text-lg font-semibold text-navy">{sectionTitle}</h2>
            </div>

            {body?.map((paragraph) => (
              <p key={paragraph} className="text-sm leading-relaxed text-text">
                {paragraph}
              </p>
            ))}

            {list && (
              <ul className="flex list-disc flex-col gap-2 pl-5 text-sm leading-relaxed text-text">
                {list.map((item) => (
                  <li key={item}>{item}</li>
                ))}
              </ul>
            )}

            {link && (
              <Link
                to={link.to}
                className="mt-auto self-start text-sm font-medium text-navy hover:underline"
              >
                {link.label}
              </Link>
            )}
          </section>
        ))}
      </div>
    </div>
  );
}
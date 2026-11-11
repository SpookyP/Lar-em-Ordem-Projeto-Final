/** Category chips for the condominium documents ("" = all). */
export function CategoryFilter({ categories, value, onChange }) {
  const chip = (active) =>
    `rounded-full px-3 py-1.5 text-sm transition ${
      active ? "bg-navy text-white" : "bg-card text-muted shadow-sm hover:text-navy"
    }`;

  return (
    <div className="flex flex-wrap gap-2" role="group" aria-label="Filtrar por categoria">
      <button type="button" onClick={() => onChange("")} className={chip(value === "")} aria-pressed={value === ""}>
        Todas
      </button>
      {categories.map((category) => (
        <button
          key={category.id}
          type="button"
          onClick={() => onChange(category.id)}
          className={chip(value === category.id)}
          aria-pressed={value === category.id}
        >
          {category.name}
        </button>
      ))}
    </div>
  );
}
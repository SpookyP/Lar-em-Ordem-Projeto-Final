import {
  CHECKLIST_EXCLUDED_CATEGORIES,
  CHECKLIST_META,
  DEFAULT_CHECKLIST_META,
} from "../data/vaultChecklist";

// one item per document type; it is attached once the home has a file of that type
export function buildChecklist(categories, documents) {
  const attachedIds = new Set(
    documents.filter((doc) => doc.hasFile).map((doc) => doc.categoryId),
  );

  return categories
    .filter((category) => !CHECKLIST_EXCLUDED_CATEGORIES.includes(category.name))
    .map((category) => {
      const meta = { ...DEFAULT_CHECKLIST_META, ...CHECKLIST_META[category.name] };

      return {
        id: category.id,
        name: category.name,
        group: meta.group,
        icon: meta.icon,
        required: meta.required,
        attached: attachedIds.has(category.id),
      };
    });
}

export function summarizeChecklist(items) {
  const total = items.length;
  const attached = items.filter((item) => item.attached).length;

  return {
    total,
    attached,
    missingRequired: items.filter((item) => item.required && !item.attached)
      .length,
    percent: total === 0 ? 0 : Math.round((attached / total) * 100),
  };
}
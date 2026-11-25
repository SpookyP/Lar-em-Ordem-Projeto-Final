const DAY = 24 * 60 * 60 * 1000;

const ITEM_KEYS = [
  "items",
  "consumption_items",
  "consumptionItems",
  "details",
  "lines",
];

// Turns a year and month into a YYYY-MM key.
export const monthKey = (year, monthIndex) =>
  `${year}-${String(monthIndex + 1).padStart(2, "0")}`;

// Turns a month key into a chronological index.
export const keyToIndex = (key) => {
  const [year, month] = key.split("-").map(Number);
  return year * 12 + month - 1;
};

// Turns a chronological index into YYYY-MM.
export const indexToKey = (index) =>
  monthKey(Math.floor(index / 12), index % 12);

// Builds the list of months between two dates, both included.
export function monthsBetween(startKey, endKey) {
  const result = [];

  for (
    let i = keyToIndex(startKey);
    i <= keyToIndex(endKey);
    i += 1
  ) {
    result.push(indexToKey(i));
  }

  return result;
}

// Turns YYYY-MM-DD (or DD/MM/YYYY) dates into a UTC timestamp; returns null when invalid.
function parseDay(value) {
  if (!value) return null;

  const text = String(value).trim().slice(0, 10);
  let year;
  let month;
  let day;

  const iso = text.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
  const pt = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

  if (iso) {
    [year, month, day] = iso.slice(1).map(Number);
  } else if (pt) {
    [day, month, year] = pt.slice(1).map(Number);
  } else {
    return null;
  }

  const time = Date.UTC(year, month - 1, day);
  const date = new Date(time);

  // Rejects dates that don't exist (e.g. 31/02), which Date would roll over.
  if (
    date.getUTCFullYear() !== year ||
    date.getUTCMonth() !== month - 1 ||
    date.getUTCDate() !== day
  ) {
    return null;
  }

  return time;
}

// Gets the month key from a timestamp.
const keyOfTime = (time) => {
  const date = new Date(time);
  return monthKey(date.getUTCFullYear(), date.getUTCMonth());
};

// Works out the first and last day of a month.
function monthBounds(key) {
  const [year, month] = key.split("-").map(Number);
  const lastDay = new Date(Date.UTC(year, month, 0)).getUTCDate();

  return [
    Date.UTC(year, month - 1, 1),
    Date.UTC(year, month - 1, lastDay),
  ];
}

// Works out how many days two periods share.
function overlapDays(aStart, aEnd, bStart, bEnd) {
  const start = Math.max(aStart, bStart);
  const end = Math.min(aEnd, bEnd);

  return end < start ? 0 : Math.round((end - start) / DAY) + 1;
}

// Converts numeric values, Portuguese money formats included.
export function asNumber(value) {
  if (typeof value === "number") {
    return Number.isFinite(value) ? value : 0;
  }

  if (value == null || value === "") return 0;

  let text = String(value)
    .trim()
    .replace(/\s/g, "")
    .replace(/[€]/g, "");

  if (text.includes(",")) {
    text = text.replace(/\./g, "").replace(",", ".");
  }

  const result = Number(text);
  return Number.isFinite(result) ? result : 0;
}

// Gets an invoice's consumptions.
export function getConsumptions(invoice) {
  const list = invoice?.consumptions ?? invoice?.utilities ?? [];
  return Array.isArray(list) ? list : [];
}

// Gets a consumption's details or lines.
export function getItems(consumption) {
  const possibleItems = ITEM_KEYS.map(
    (key) => consumption?.[key]
  );

  return (
    possibleItems.find(
      (items) => Array.isArray(items) && items.length > 0
    ) ??
    possibleItems.find(Array.isArray) ??
    []
  );
}

// Patterns used to identify the service by name (without accents).
const TYPE_NAME_PATTERNS = [
  [1, /eletric|electric|energia/],
  [2, /agua|water/],
  [3, /\bgas\b/],
];

// Identifies the service type (1 = electricity, 2 = water, 3 = gas).
// Single source for every component: the id first, then the name.
export function getTypeId(consumption) {
  const raw =
    consumption?.consumption_type_id ??
    consumption?.consumptionTypeId ??
    consumption?.consumption_type?.id ??
    consumption?.consumptionType?.id ??
    consumption?.type_id ??
    consumption?.type?.id;

  const id = Number(raw);

  if ([1, 2, 3].includes(id)) return id;

  const name =
    [
      consumption?.consumption_type?.name,
      consumption?.consumptionType?.name,
      consumption?.type?.name,
      consumption?.type,
      consumption?.name,
    ].find((value) => typeof value === "string" && value.trim()) ?? "";

  const normalized = name
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase();

  return (
    TYPE_NAME_PATTERNS.find(([, pattern]) => pattern.test(normalized))?.[0] ??
    null
  );
}

// Gets the cost of a consumption line.
export function itemCost(item) {
  return asNumber(
    item?.cost ??
    item?.total_cost ??
    item?.totalCost ??
    item?.amount_cost ??
    item?.price ??
    0
  );
}

// Works out a consumption's total cost.
function getConsumptionCost(consumption) {
  const items = getItems(consumption);

  if (items.length > 0) {
    return items.reduce(
      (sum, item) => sum + itemCost(item),
      0
    );
  }

  return asNumber(
    consumption?.cost ??
    consumption?.total_cost ??
    consumption?.totalCost ??
    0
  );
}

// Works out the billing period.
function getPeriod(invoice, consumption) {
  const start = parseDay(
    consumption?.period_start ??
    invoice?.period_start ??
    invoice?.issue_date
  );

  const end = parseDay(
    consumption?.period_end ??
    invoice?.period_end ??
    invoice?.issue_date
  );

  if (start == null || end == null) return null;

  return end >= start ? [start, end] : [end, start];
}

/**
 * Spreads the invoice costs over their months.
 */
export function buildMonthlySeries(invoices, serviceFilter = "all") {
  const totals = new Map();

  (invoices ?? []).forEach((invoice) => {
    getConsumptions(invoice).forEach((consumption) => {
      const typeId = getTypeId(consumption);

      if (!typeId) return;

      // Applies the service filter.
      if (
        serviceFilter !== "all" &&
        Number(serviceFilter) !== typeId
      ) {
        return;
      }

      const period = getPeriod(invoice, consumption);

      if (!period) return;

      const [start, end] = period;
      const totalDays = Math.round((end - start) / DAY) + 1;
      const cost = getConsumptionCost(consumption);

      // Spreads the cost proportionally over the months it covers.
      monthsBetween(keyOfTime(start), keyOfTime(end)).forEach(
        (key) => {
          const [monthStart, monthEnd] = monthBounds(key);
          const days = overlapDays(
            start,
            end,
            monthStart,
            monthEnd
          );

          if (days > 0) {
            totals.set(
              key,
              (totals.get(key) ?? 0) +
                (cost * days) / totalDays
            );
          }
        }
      );
    });
  });

  return totals;
}

/**
 * Scales the consumptions to the selected range.
 */
export function scaleInvoicesToRange(invoices, range) {
  if (!range?.start || !range?.end) return invoices ?? [];

  const chronologicalStart = Math.min(
    keyToIndex(range.start),
    keyToIndex(range.end)
  );

  const chronologicalEnd = Math.max(
    keyToIndex(range.start),
    keyToIndex(range.end)
  );

  const [rangeStart] = monthBounds(
    indexToKey(chronologicalStart)
  );

  const [, rangeEnd] = monthBounds(
    indexToKey(chronologicalEnd)
  );

  return (invoices ?? [])
    .map((invoice) => {
      const consumptions = getConsumptions(invoice)
        .map((consumption) => {
          const period = getPeriod(invoice, consumption);

          if (!period) return null;

          const [start, end] = period;
          const totalDays = Math.round((end - start) / DAY) + 1;

          const inside = overlapDays(
            start,
            end,
            rangeStart,
            rangeEnd
          );

          if (inside === 0) return null;

          const factor = inside / totalDays;
          const scaled = { ...consumption };

          const consumptionCostKey = [
            "cost",
            "total_cost",
            "totalCost",
          ].find((key) => scaled[key] != null);

          if (consumptionCostKey) {
            scaled[consumptionCostKey] =
              asNumber(scaled[consumptionCostKey]) * factor;
          }

          // Scales the individual items' costs.
          ITEM_KEYS.forEach((key) => {
            if (Array.isArray(scaled[key])) {
              scaled[key] = scaled[key].map((item) => {
                const itemKey = [
                  "cost",
                  "total_cost",
                  "totalCost",
                  "amount_cost",
                  "price",
                ].find(
                  (candidate) => item?.[candidate] != null
                );

                return itemKey
                  ? {
                      ...item,
                      [itemKey]: itemCost(item) * factor,
                    }
                  : {
                      ...item,
                      cost: itemCost(item) * factor,
                    };
              });
            }
          });

          return scaled;
        })
        .filter(Boolean);

      return {
        ...invoice,
        consumptions,
      };
    })
    .filter((invoice) => invoice.consumptions.length > 0);
}
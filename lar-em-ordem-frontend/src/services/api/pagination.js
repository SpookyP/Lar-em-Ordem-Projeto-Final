export function cleanParams(params) {
    return Object.fromEntries(
        Object.entries(params).filter(([,value]) => value != null && value !== ""),
    );
}

export function toPage(response, mapItem) {
  const { data, meta } = response.data;
  return {
    items: data.map(mapItem),
    lastPage: meta.last_page,
    total: meta.total,
  };
}
import { useState, useEffect } from "react";

const EMPTY = { key: null, items: [], lastPage: 1, total: 0, error: "" };

export function usePagedList(
  fetchPage,
  params,
  { enabled = true, errorMessage = "Não foi possível carregar a lista." } = {},
) {
  const paramsKey = JSON.stringify(params);
  const [pageState, setPageState] = useState({ key: paramsKey, page: 1 });
  const [version, setVersion] = useState(0);
  const [result, setResult] = useState(EMPTY);

  const page = pageState.key === paramsKey ? pageState.page : 1;
  const requestKey = `${paramsKey}|${page}|${version}`;

  useEffect(() => {
    if (!enabled) return;
    let active = true;
    fetchPage({ ...JSON.parse(paramsKey), page })
      .then((data) => {
        if (active) setResult({ ...data, key: requestKey, error: "" });
      })
      .catch(() => {
        if (active) setResult({ ...EMPTY, key: requestKey, error: errorMessage });
      });
    return () => {
      active = false;
    };
  }, [fetchPage, paramsKey, page, requestKey, enabled, errorMessage]);

  return {
    items: result.items,
    page,
    totalPages: result.lastPage,
    total: result.total,
    loading: enabled && result.key !== requestKey,
    error: result.error,
    setPage: (next) => setPageState({ key: paramsKey, page: next }),
    reload: () => setVersion((v) => v + 1),
  };
}
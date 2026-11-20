import { useEffect, useRef, useState } from "react";
import { getErrorMessage } from "../utils/apiError";

const EMPTY = { key: null, data: null, error: "" };

//Runs fetcher whenever deps change and returns { data, loading, error, reload }
//loading is derived from a key comparison (no setState in the effect body),
//the same approach as usePagedList, so it respects the react-hooks rules.
export function useFetch(
  fetcher,
  deps = [],
  { enabled = true, errorMessage } = {},
) {
  //keep the latest fetcher without turning it into an effect dependency
  //so the fetch effect can call it without listing it as a dependency
  const fetcherRef = useRef(fetcher);
  useEffect(() => {
    fetcherRef.current = fetcher;
  }, [fetcher]);

  const depsKey = JSON.stringify(deps);
  const [version, setVersion] = useState(0);
  const [result, setResult] = useState(EMPTY);

  const requestKey = `${depsKey}|${version}`;

  useEffect(() => {
    if (!enabled) return;
    let active = true;
    fetcherRef
      .current()
      .then((data) => {
        if (active) setResult({ key: requestKey, data, error: "" });
      })
      .catch((err) => {
        if (active) {
          setResult({
            key: requestKey,
            data: null,
            error: getErrorMessage(err, errorMessage),
          });
        }
      });
    return () => {
      active = false;
    };
  }, [requestKey, enabled, errorMessage]);

  return {
    data: result.data,
    loading: enabled && result.key !== requestKey,
    error: result.error,
    reload: () => setVersion((v) => v + 1),
  };
}

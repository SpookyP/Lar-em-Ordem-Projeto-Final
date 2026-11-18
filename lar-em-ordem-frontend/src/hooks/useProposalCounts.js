import { useEffect, useState } from "react";
import { getMyProposals } from "../services/api/proposalService";
import { PROPOSAL_STATUS } from "../data/assistanceInfo";

const STATUSES = Object.keys(PROPOSAL_STATUS);

export function useProposalCounts(version) {
  const [counts, setCounts] = useState(null);

  useEffect(() => {
    let active = true;

    Promise.all(STATUSES.map((status) => getMyProposals({ page: 1, perPage: 1, status })))
      .then((pages) => {
        if (!active) return;
        const byStatus = Object.fromEntries(STATUSES.map((status, i) => [status, pages[i].total]));
        const all = Object.values(byStatus).reduce((sum, n) => sum + n, 0);
        setCounts({ all, ...byStatus });
      })
      .catch(() => {
        if (active) setCounts(null);
      });

    return () => {
      active = false;
    };
  }, [version]);

  return counts;
}
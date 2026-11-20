import { getMyProposals } from "../services/api/proposalService";
import { PROPOSAL_STATUS } from "../data/assistanceInfo";
import { useFetch } from "./useFetch";

const STATUSES = Object.keys(PROPOSAL_STATUS);

export function useProposalCounts(version) {
  const { data } = useFetch(
    () =>
      Promise.all(
        STATUSES.map((status) =>
          getMyProposals({ page: 1, perPage: 1, status }),
        ),
      ).then((pages) => {
        const byStatus = Object.fromEntries(
          STATUSES.map((status, i) => [status, pages[i].total]),
        );
        const all = Object.values(byStatus).reduce((sum, n) => sum + n, 0);
        return { all, ...byStatus };
      }),
    [version],
  );

  return data;
}

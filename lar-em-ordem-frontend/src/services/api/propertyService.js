import { exampleHouses } from "../../data/exampleData";

const PER_PAGE = 5;

//ENQUANTO NAO LIGA AO BACKEND
export async function getProperties(page = 1) {
  const start = (page - 1) * PER_PAGE;
  const houses = exampleHouses.slice(start, start + PER_PAGE);
  const lastPage = Math.ceil(exampleHouses.length / PER_PAGE);

  return { houses, currentPage: page, lastPage };
}

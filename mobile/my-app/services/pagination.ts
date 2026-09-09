import { apiRequest } from './api';
/** Read every page so lists do not silently stop at the backend's default limit. */
export async function fetchAllPages<T>(endpoint:string): Promise<T[]> {
  const rows:T[]=[]; let page=1, pages=1;
  do {
    const result=await apiRequest<{data:T[];pagination?:{total_pages:number}}>(endpoint+(endpoint.includes('?')?'&':'?')+'page='+page+'&limit=100');
    rows.push(...result.data); pages=result.pagination?.total_pages ?? 1; page++;
  } while(page<=pages);
  return rows;
}

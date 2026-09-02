import http from 'k6/http';
import { check } from 'k6';

export const options = {
  vus: 20,
  duration: '30s',
  thresholds: { http_req_failed: ['rate<0.01'], http_req_duration: ['p(95)<800'] },
};

const baseUrl = __ENV.BASE_URL || 'http://127.0.0.1:8092';
const bloggerCode = __ENV.BLOGGER_CODE || 't525';

export default function () {
  const response = http.get(`${baseUrl}/s/${bloggerCode}`);
  check(response, { 'landing is successful': (result) => result.status === 200 });
}

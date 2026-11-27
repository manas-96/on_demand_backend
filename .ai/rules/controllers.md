---
paths:
  - 'app/Http/Controllers/**/*Support*'
---

# Controllers

## Support tickets live in two stores
App support chats (customer/provider apps) are Firestore documents, loaded client-side in admin/support index + chat views; routes /admin/support/{id} take a Firestore id. Website /support form tickets are the support_tickets DB table (SupportTicket), shown server-side in the "Website Requests" list and managed via /admin/support/web/{supportTicket} (route-model bound). Never point a DB ticket at the Firestore routes or vice versa; statuses for DB tickets are SupportTicket::STATUSES.

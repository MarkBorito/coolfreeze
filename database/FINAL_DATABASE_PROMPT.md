# CoolFreeze Final Database Design Prompt

Copy and use this prompt when asking an AI or developer to design the final CoolFreeze database. Review the open business decisions with the project owner before treating the result as final.

---

## Prompt

You are a senior database designer. Design the final relational database for **CoolFreeze**, a PHP/MySQL air-conditioning service management application running locally on XAMPP. Inspect the project files and base the design on the actual workflows described below. The existing `database/schema.sql` is preliminary and is **not** the final specification; it currently defines only a `customers` table. Do not assume it is complete, and do not overwrite or migrate existing data without an explicit migration plan.

### Application context

- Runtime: PHP with `mysqli`, MySQL/MariaDB via XAMPP, and `utf8mb4`.
- Existing database name and PHP connection default: `coolfreeze_db`.
- Existing customer signup stores username, email, phone, password hash, terms agreement, and creation time. Username and email are currently unique.
- Customer-facing screens include registration/login, profile, services, service cart, service scheduling/request submission, request history/details, and completion proof.
- Much of the profile, catalog, cart, and request content is currently temporary hard-coded data. Treat the screens as workflow evidence, not as proof that the business rules are finalized.

### Workflows and data supported by the current project

1. **Customer accounts and profile**
   - Registration fields: username, email, phone, password, and acceptance of Terms of Service and Privacy Policy.
   - Profile fields shown: username, full name, birthday, address, email, phone, and profile image.
   - Passwords must be stored as password hashes, never plaintext.
   - The UI includes forgot-password, a five-digit verification-code screen, and password reset. Design a secure way to store one-time verification/reset tokens or codes, expiry, use state, and attempt/rate-limit metadata as appropriate. Do not store a raw reset secret if a hash can be stored instead.
   - Do not assume email verification is implemented: identify it as a separate requirement or open decision.

2. **Service catalog and pricing**
   - Service examples: AC Cleaning, AC Repair, AC Maintenance, AC Installation, and Parts Replacement.
   - Catalog content may need a name, description, starting/base price, active state, and timestamps.
   - Prices can depend on AC unit type or final inspection. The UI labels prices as starting estimates and says the final price may be confirmed before work begins.
   - Preserve the price/description applicable at request time so catalog edits do not rewrite historical requests.
   - Do not invent a complete service list, unit-type list, price matrix, or tax/payment policy. Mark missing choices for owner confirmation.

3. **Cart and service requests**
   - Customers can select multiple services and submit them together as one request.
   - A request includes a preferred date, preferred time slot, complete service address, description/instructions, contact name, and contact phone.
   - Request history shows a request number, submission date, requested services, estimated total, address, preferred schedule, status, and status detail.
   - Statuses currently shown include Pending, Confirmed, On going, Completed, and Cancelled. Store statuses consistently (for example, with a lookup table or constrained values) and define valid transitions only after owner confirmation.
   - Model request line items separately from requests. Store quantity and price snapshots for each line item; define how totals are calculated and whether admins can revise estimates.
   - The cart is currently session-backed/sample data. Recommend whether to keep it in the PHP session or persist it, and explain the tradeoff. Do not add cart tables unless there is a justified requirement.
   - A customer may cancel before technician assignment according to the FAQ; make the cancellation rule configurable/documented rather than enforcing an unconfirmed policy in database triggers.

4. **Technicians, assignment, and service completion**
   - Completed-request UI can display assigned technicians, completion time, service notes, cost, photos, and a receipt link.
   - Design entities/relationships for technician assignment and completion records/proof if appropriate. Consider that one request may involve multiple technicians, and that work/assignment history may need to be retained.
   - File uploads should generally store metadata and a safe relative storage path, not image/receipt binary data in MySQL, unless there is a specific reason otherwise.
   - The project does not yet establish technician/admin registration, roles, assignment rules, or receipt/payment behavior. Call these out as open decisions and present a minimal viable option without pretending it is confirmed.

5. **Addresses and request contact information**
   - The profile has an address field, and each service request has a service address and contact details.
   - Recommend whether customers need multiple saved addresses. Regardless, snapshot the address/contact used for each submitted request so later profile edits do not alter past records.

### Design requirements

- Use InnoDB, `utf8mb4`, appropriate numeric/date/time types, primary keys, foreign keys, unique constraints, indexes, and nullability.
- Use consistent snake_case names and explicit delete/update behavior for foreign keys. Avoid cascading deletion of customer/request history unless there is a deliberate retention policy.
- Add created/updated timestamps where useful, and soft-delete/archive fields only when justified.
- Avoid storing derived totals as the only source of truth. If a total snapshot is stored for reporting, explain how it is kept consistent with line items.
- Support pagination/filtering of a customer's requests by status and newest submission date.
- Consider privacy and retention for account data, verification/reset records, uploaded files, and operational history.
- Keep the design appropriate for this PHP/MySQL project; do not introduce a different database platform or unnecessary infrastructure.

### Required response and deliverables

1. First summarize what the repository currently implements versus what is only represented by temporary UI data.
2. List the unanswered business decisions that block a truly final schema. Separate required decisions from reasonable defaults, and ask concise questions for the project owner.
3. Propose a normalized entity-relationship design. For each table, explain its purpose, important columns, keys, constraints, and relationships. Include a Mermaid ER diagram if useful.
4. Provide a complete, runnable MySQL/MariaDB `schema.sql` for a fresh `coolfreeze_db`, with correct statement ordering and no reliance on pre-existing tables.
5. Provide optional sample/seed SQL separately from the schema. Never include real personal data, production credentials, or a reusable plaintext password. Clearly document any development-only credentials/hash assumptions.
6. Provide a migration plan from the current one-table schema, including how to preserve existing customers, handle duplicate/invalid data, and back up first. Do not execute destructive SQL.
7. Map existing PHP reads/writes to the proposed tables and identify PHP/API work still needed; do not claim the database alone makes the UI workflows functional.
8. Validate the SQL for MySQL/MariaDB compatibility and explain how to run it in XAMPP. Explicitly note any feature that requires a version-specific syntax choice.

Do not silently invent critical business rules. Where a decision remains open, give a conservative default, label it clearly, and make the schema easy to adjust. Do not modify project files unless explicitly asked to implement the approved design.

# Business switcher and approval fix

This update corrects the account structure to one user -> one merchant -> many businesses.

- Adding a business creates a pending `businesses` row only.
- No fake activation/KYC record is created.
- The new business becomes active and opens `/merchant/activate`.
- The switcher reads all businesses belonging to the single merchant.
- Activation submissions are linked using `business_id`.
- Admin approval updates the selected business and never creates another merchant.
- Admin rejection is implemented.
- A repair migration removes only placeholder KYC records created by the old buggy auto-approval controller.

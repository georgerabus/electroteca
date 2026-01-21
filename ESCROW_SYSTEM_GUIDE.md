# 🔐 Escrow & Dispute Resolution System Documentation

## Overview

This document describes the complete **Escrow & Dispute Resolution System** for the Electroteca platform. This system ensures trust between borrowers and sellers when exchanging high-value items by holding funds in escrow and providing a fair dispute resolution mechanism.

## 🏗️ Architecture

### Core Components

#### 1. **EscrowTransaction Model** (`app/Models/EscrowTransaction.php`)
- Represents held funds during order fulfillment
- Linked to Order and WalletTransaction
- Tracks status: `held`, `released`, `deducted`, `refunded`, `cancelled`, `awaiting_resolution`
- Stores metadata about the escrow lifecycle

#### 2. **Dispute Model** (`app/Models/Dispute.php`)
- Represents disagreements between buyer and seller
- Linked to Order, initiator User, respondent User, and resolver admin
- Has many evidence submissions and history records
- Supports appeals for reopened disputes

#### 3. **DisputeEvidence Model** (`app/Models/DisputeEvidence.php`)
- Stores evidence submissions (photos, videos, receipts, etc.)
- Linked to Dispute and User
- Tracks evidence type for categorization

#### 4. **DisputeHistory Model** (`app/Models/DisputeHistory.php`)
- Timeline of dispute actions
- Stores action type, description, involved user, and metadata
- Useful for audit trails and status tracking


## 🎯 Business Logic

### EscrowService (`app/Services/EscrowService.php`)

#### Key Methods

**1. Hold Funds**
```php
holdFunds(Order $order, float $amount, int $inspectionPeriodDays = 7): EscrowTransaction
```
- Initiated when order is placed and payment confirmed
- Deducts amount from borrower's wallet
- Sets return deadline based on inspection period
- Creates wallet transaction record

**2. Release Funds**
```php
releaseFunds(EscrowTransaction $escrow, string $reason = 'on_time_return'): EscrowTransaction
```
- Credits funds to seller's wallet
- Triggered when item returned on time in good condition
- Updates order status to reflect completion

**3. Deduct for Damage**
```php
deductForDamage(EscrowTransaction $escrow, float $damageAmount, string $description): array
```
- Partially deducts escrow for damages
- Returns remaining balance to seller
- Charges damage fee to borrower
- Records damage description

**4. Refund Escrow**
```php
refundEscrow(EscrowTransaction $escrow, string $reason = 'order_cancelled'): EscrowTransaction
```
- Refunds entire escrow to borrower
- Used for cancelled orders or disputes won by borrower

**5. Handle Dispute Resolution**
```php
handleDisputeResolution(EscrowTransaction $escrow, string $resolution, float $damageAmount, string $reason): array
```
- Distributes funds based on dispute outcome
- Supports three resolutions: `initiator_wins`, `respondent_wins`, `compromise`
- Updates wallet transactions for both parties

### DisputeService (`app/Services/DisputeService.php`)

#### Key Methods

**1. Create Dispute**
```php
createDispute(Order $order, User $initiator, string $title, string $description, string $reason, ?float $damageClaimAmount): Dispute
```
- Creates new dispute for order
- Can be initiated by buyer or seller
- Freezes escrow to `awaiting_resolution` status
- Records initial history entry

**2. Submit Evidence**
```php
submitEvidence(Dispute $dispute, User $user, string $fileUrl, string $evidenceType, ?string $description): DisputeEvidence
```
- Allows both parties to submit evidence
- Supports multiple types: photo, video, receipt, message, document
- Prevents evidence submission after resolution (unless appealed)

**3. Resolve Dispute**
```php
resolveDispute(Dispute $dispute, User $resolver, string $resolution, string $resolutionNotes, ?float $approvedDeduction): Dispute
```
- Admin-only method to finalize dispute
- Handles escrow distribution via `EscrowService`
- Records resolver information and decision notes

**4. Appeal Dispute**
```php
appealDispute(Dispute $dispute, User $appellant, string $appealNotes, array $appealEvidenceUrls): Dispute
```
- Reopens resolved dispute for re-review
- Stores appeal notes and additional evidence
- Resets status to `awaiting_resolution`

**5. Dispute Statistics**
```php
getUserDisputeStats(User $user): array
getUserDisputeStats(User $user): array
```
- Returns win/loss records, total disputes, appeal counts
- Useful for reputation calculation

## 🔄 Workflow Examples

### Scenario 1: Normal Return (On-Time)

```
1. Order Created
   → EscrowService::holdFunds() called
   → Borrower's wallet debited
   → Escrow status: "held"
   → Return deadline set (e.g., 7 days)

2. Item Returned In Good Condition
   → EscrowService::releaseFunds() called
   → Escrow status: "released"
   → Seller's wallet credited
   → Order marked completed

3. Result
   ✓ Borrower: Full refund to wallet
   ✓ Seller: Receives full payment
   ✓ Platform: Transaction complete
```

### Scenario 2: Damage Reported

```
1. Item Returned with Damage
   → Seller/Admin reports damage
   → EscrowService::deductForDamage() called
   → Damage amount deducted from escrow
   → Remaining balance released to seller

2. Result
   ✓ Borrower: Charged damage fee
   ✓ Seller: Receives reduced payment
   ✓ Platform: Fair settlement
```

### Scenario 3: Dispute - Item Not Received

```
1. Dispute Created
   → Borrower initiates dispute with title "Item Not Received"
   → Escrow status changed to "awaiting_resolution"
   → DisputeService::createDispute() called
   → Event dispatched: DisputeCreated

2. Evidence Submission
   → Seller submits tracking screenshot
   → Borrower submits photos of empty box
   → Both use DisputeService::submitEvidence()

3. Admin Resolution
   → Admin reviews evidence
   → Decides "initiator_wins" (borrower right)
   → DisputeService::resolveDispute() called
   → EscrowService::handleDisputeResolution() distributes funds

4. Result
   ✓ Borrower: Full refund
   ✓ Seller: Charged full amount
   ✓ Dispute closed with resolution
```

### Scenario 4: Dispute Appeal

```
1. Dispute Resolved (Seller Wins)
   → Seller receives payment
   → Borrower sees resolution

2. Buyer Appeals
   → DisputeService::appealDispute() called
   → Status reset to "awaiting_resolution"
   → Appeal notes and new evidence submitted
   → Sent back to admin for review

3. Re-Resolution
   → Admin reviews appeal
   → Makes final decision
   → Dispute marked as closed permanently
```

## 📡 API Endpoints

### Escrow Endpoints

```
POST   /api/escrow/orders/{order}/hold              - Hold funds in escrow
POST   /api/escrow/orders/{order}/release           - Release funds to seller
POST   /api/escrow/orders/{order}/deduct-damage     - Deduct for damages
POST   /api/escrow/orders/{order}/refund            - Refund to borrower
GET    /api/escrow/orders/{order}                   - Get escrow details
GET    /api/escrow/orders/{order}/all               - Get all escrow transactions
GET    /api/escrow/history                          - User's escrow history
GET    /api/escrow/statistics                       - User's escrow stats
```

### Dispute Endpoints

```
POST   /api/disputes/orders/{order}                 - Create dispute
GET    /api/disputes/{dispute}                      - Get dispute details
POST   /api/disputes/{dispute}/evidence             - Submit evidence
POST   /api/disputes/{dispute}/resolve              - Resolve (admin only)
POST   /api/disputes/{dispute}/appeal               - Appeal dispute
POST   /api/disputes/{dispute}/close                - Close (admin only)
GET    /api/disputes/{dispute}/timeline             - Get dispute history
GET    /api/disputes/my/list                        - User's disputes
GET    /api/disputes/my/open                        - User's open disputes
GET    /api/disputes/my/statistics                  - User's dispute stats
GET    /api/disputes/orders/{order}/statistics      - Order dispute stats
GET    /api/disputes/admin/awaiting-resolution      - Admin dashboard (admin only)
```

## 🔧 Integration with Existing Systems

### Payment Integration
```
Order Payment Complete
  ↓
PaymentService marks payment as completed
  ↓
EscrowService::holdFunds() called
  ↓
Funds held in escrow
```

### Wallet Integration
```
EscrowService uses WalletService to:
- Debit borrower's wallet when holding funds
- Credit seller's wallet when releasing funds
- Record all transactions with metadata
```

### Reputation Integration
- Dispute wins/losses can affect user reputation
- Frequent disputes can lower reputation score
- Successful transactions increase reputation

## 🛡️ Security & Integrity

### Validation
- Only involved parties can submit evidence
- Only admins can resolve disputes
- Escrow amounts verified against wallet balance
- Damage amounts cannot exceed escrow amount

### Data Integrity
- All operations are audited via DisputeHistory
- Wallet transactions linked to escrow
- Immutable audit trail
- Timestamps for all state changes

### Business Rules
- Return deadlines enforced
- Evidence only accepted before resolution (or during appeal)
- One active escrow per order
- Disputes can only be appealed once per resolution

## 📊 Reporting & Analytics

### User Statistics
- Total disputes: as initiator and respondent
- Win rates for each role
- Total escrow amount handled
- Damage charges received/paid

### Order Statistics
- Dispute history for each order
- Current escrow status
- Return deadline status
- Total escrow held

### Admin Dashboard
- Disputes awaiting resolution (priority view)
- Recent appeals
- User dispute patterns (potential fraud detection)
- Escrow distribution trends

## 🚀 Usage Examples

### Hold Escrow on Order Completion
```php
$order = Order::find($orderId);
$escrow = $escrowService->holdFunds(
    order: $order,
    amount: $order->total_amount,
    inspectionPeriodDays: 7
);
// Notify parties via event
event(new EscrowReleased($escrow));
```

### Create Dispute
```php
$dispute = $disputeService->createDispute(
    order: $order,
    initiator: auth()->user(),
    title: "Item arrived damaged",
    description: "Screen is cracked...",
    reason: "item_damaged",
    damageClaimAmount: 150.00
);
// Notify parties
$dispute->initiator->notify(new DisputeNotification($dispute, 'created'));
$dispute->respondent->notify(new DisputeNotification($dispute, 'created'));
```

### Resolve Dispute
```php
$resolved = $disputeService->resolveDispute(
    dispute: $dispute,
    resolver: $admin,
    resolution: "compromise",
    resolutionNotes: "Damage claim approved at 50% of value",
    approvedDeduction: 75.00
);
// Notify both parties
$dispute->initiator->notify(new DisputeNotification($resolved, 'resolved'));
$dispute->respondent->notify(new DisputeNotification($resolved, 'resolved'));
```

## 🧪 Testing

Factories available:
- `DisputeFactory`: Generate test disputes with states
- `EscrowTransactionFactory`: Generate test escrow records

Example:
```php
$dispute = Dispute::factory()->resolved()->create();
$escrow = EscrowTransaction::factory()->deducted()->create();
```

## 🔔 Events & Notifications

### Events
- `DisputeCreated`: Dispatch when dispute created
- `DisputeResolved`: Dispatch when dispute resolved
- `EscrowReleased`: Dispatch when escrow released
- `EscrowDamageDeducted`: Dispatch when damage deducted

### Notifications
- `DisputeNotification`: Email + database notification
- `EscrowNotification`: Email + database notification

## ⚙️ Configuration

Add to `.env` if needed:
```env
ESCROW_DEFAULT_INSPECTION_DAYS=7
DISPUTE_AUTO_RESOLVE_DAYS=30
```

## 🎓 Key Concepts

### Escrow
Money held by neutral third party (platform) until conditions are met

### Dispute Resolution
Structured process for settling disagreements with evidence and arbitration

### Compromise Resolution
Option to split damages: seller gets partial payment, buyer charged reduced fee

### Appeal
Mechanism to challenge a resolved dispute for second review

### Audit Trail
Complete history of all actions for transparency and accountability

---

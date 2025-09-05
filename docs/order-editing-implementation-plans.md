# Order Editing Implementation Plans

Based on the analysis of your event-sourced order system, here are three implementation plans for order editing functionality. Each plan represents a different approach with varying complexity and features.

## Plan A: Minimal Viable Solution (Recommended for MVP)
**Timeline: 1-2 weeks**
**Complexity: Low**
**Risk: Minimal**

### Approach
Implement basic order modifications for orders in early stages (draft, placed) only. Once an order reaches the kitchen (confirmed status), only allow cancellation.

### Implementation
```php
// 1. Add simple modification event
class OrderItemsUpdated extends ShouldBeStored {
    public function __construct(
        public string $aggregateRootUuid,
        public array $newItems,  // Complete replacement
        public string $modifiedBy,
        public Carbon $modifiedAt
    ) {}
}

// 2. Aggregate method
public function updateItems(array $items, string $modifiedBy): self {
    if (!in_array($this->status, ['draft', 'placed'])) {
        throw new InvalidOrderStateException("Cannot modify order in {$this->status} status");
    }
    
    $this->recordThat(new OrderItemsUpdated(...));
    // Recalculate promotions and totals
    $this->recalculatePricing();
    return $this;
}
```

### Features
- ✅ Edit items before kitchen confirmation
- ✅ Simple "replace all items" approach
- ✅ Automatic promotion recalculation
- ✅ Basic audit trail
- ❌ No partial modifications after confirmation
- ❌ No payment adjustments

### When to Choose
- Need quick solution for launch
- Restaurant has simple workflow
- Most edits happen before order confirmation
- Payment is typically handled at the end

---

## Plan B: Compensating Events with Restrictions (Recommended)
**Timeline: 3-4 weeks**
**Complexity: Medium**
**Risk: Low**

### Approach
Implement the compensating events pattern with smart restrictions based on order status. Allow modifications throughout the order lifecycle with appropriate business rules.

### Implementation
```php
// 1. Granular modification events
class ItemsModified extends ShouldBeStored {
    public function __construct(
        public string $aggregateRootUuid,
        public array $addedItems,
        public array $removedItems,
        public array $quantityChanges,
        public string $reason,
        public string $modifiedBy,
        public bool $requiresKitchenNotification
    ) {}
}

// 2. Status-aware modifications
public function modifyOrder(
    OrderModificationData $modification,
    string $modifiedBy
): self {
    $permissions = $this->getEditPermissions();
    
    if (!$permissions->canModify) {
        throw new OrderNotModifiableException();
    }
    
    // Apply different rules based on status
    match($this->status) {
        'draft', 'placed' => $this->applyFullModification($modification),
        'confirmed' => $this->applyRestrictedModification($modification),
        'preparing' => $this->applyAdditionsOnly($modification),
        default => throw new InvalidOrderStateException()
    };
    
    return $this;
}
```

### Features
- ✅ Granular item modifications (add, remove, update)
- ✅ Status-based edit restrictions
- ✅ Kitchen notification system
- ✅ Payment difference handling
- ✅ Comprehensive audit trail
- ✅ Manager override capabilities
- ⚠️ Requires staff training on restrictions

### Payment Handling
```php
// Automatic payment adjustment
if ($order->hasPayment()) {
    $difference = $newTotal - $originalTotal;
    
    if ($difference > 0) {
        // Create supplementary payment
        PaymentService::chargeAdditional($order, $difference);
    } elseif ($difference < 0) {
        // Process partial refund
        PaymentService::refundPartial($order, abs($difference));
    }
}
```

### When to Choose
- Need production-ready solution
- Want balance between flexibility and control
- Have clear business rules for modifications
- Ready to train staff on new features

---

## Plan C: Full Version Control System
**Timeline: 6-8 weeks**
**Complexity: High**
**Risk: Medium**

### Approach
Implement a complete version control system for orders, allowing preview of changes, approval workflows, and rollback capabilities.

### Implementation
```php
// 1. Version control events
class OrderVersionCreated extends ShouldBeStored {
    public function __construct(
        public string $aggregateRootUuid,
        public int $versionNumber,
        public string $basedOnVersion,
        public array $changes,
        public string $createdBy
    ) {}
}

class OrderVersionApproved extends ShouldBeStored {
    public function __construct(
        public string $aggregateRootUuid,
        public int $versionNumber,
        public string $approvedBy,
        public Carbon $effectiveAt
    ) {}
}

// 2. Version management
class OrderAggregate {
    protected array $versions = [];
    protected int $activeVersion = 1;
    
    public function createDraftVersion(array $changes): self {
        $newVersion = $this->activeVersion + 1;
        $this->recordThat(new OrderVersionCreated(...));
        return $this;
    }
    
    public function approveVersion(int $version, string $approvedBy): self {
        // Validate approval permissions
        // Apply version changes
        // Handle payment reconciliation
    }
    
    public function rollbackToVersion(int $version): self {
        // Revert to previous version
        // Create compensating events
    }
}
```

### Features
- ✅ Complete version history
- ✅ Preview changes before applying
- ✅ Approval workflows for significant changes
- ✅ Rollback capabilities
- ✅ Comparison between versions
- ✅ Scheduled modifications
- ❌ Complex implementation
- ❌ Higher storage requirements
- ❌ Steeper learning curve

### UI Requirements
```typescript
// Version comparison view
interface OrderVersionComparison {
    showDiff(v1: OrderVersion, v2: OrderVersion): DiffResult;
    previewVersion(version: OrderVersion): OrderPreview;
    listVersionHistory(): VersionTimeline;
}
```

### When to Choose
- Enterprise environment with compliance requirements
- Need for approval workflows
- Complex multi-step order modifications
- Requirement for complete rollback capabilities

---

## Decision Matrix

| Criteria | Plan A (Minimal) | Plan B (Compensating) | Plan C (Versioned) |
|----------|-----------------|---------------------|-------------------|
| Implementation Time | 1-2 weeks | 3-4 weeks | 6-8 weeks |
| Complexity | Low | Medium | High |
| Maintenance | Easy | Moderate | Complex |
| Staff Training | Minimal | Moderate | Extensive |
| Audit Trail | Basic | Complete | Comprehensive |
| Payment Handling | Manual | Automatic | Full Reconciliation |
| Kitchen Integration | No | Yes | Yes |
| Mobile Sync | Simple | Good | Complex |
| Rollback Support | No | Limited | Full |
| Cost | $ | $$ | $$$ |

## Recommendation

**For Colame, I recommend Plan B (Compensating Events with Restrictions)** because:

1. **Right Balance**: Provides necessary flexibility without overwhelming complexity
2. **Event-Sourcing Aligned**: Natural fit with your existing architecture
3. **Restaurant-Friendly**: Matches typical restaurant workflows
4. **Mobile-Ready**: Events sync well with offline mobile apps
5. **Future-Proof**: Can evolve to Plan C if needed later
6. **Quick ROI**: Can be implemented incrementally

## Next Steps

1. **Choose a plan** based on your business requirements
2. **Create detailed specifications** for chosen approach
3. **Set up test environment** with sample data
4. **Implement core events** and aggregate methods
5. **Build UI components** for order editing
6. **Test with restaurant staff** in staging environment
7. **Deploy with feature flags** for gradual rollout

## Quick Start for Plan B

If you approve Plan B, here's what we'll build first:

### Week 1: Backend Foundation
- [ ] Create ItemsModified event
- [ ] Implement modifyOrder method in aggregate
- [ ] Add status-based permissions
- [ ] Create modification service

### Week 2: Payment Integration
- [ ] Payment difference calculator
- [ ] Refund service integration
- [ ] Additional charge handling
- [ ] Transaction logging

### Week 3: Frontend Implementation
- [ ] Create order/edit.tsx page
- [ ] Build modification form
- [ ] Add payment preview
- [ ] Implement authorization modal

### Week 4: Testing & Deployment
- [ ] Integration testing
- [ ] User acceptance testing
- [ ] Staff training materials
- [ ] Production deployment

Would you like to proceed with Plan B, or would you prefer to discuss the alternatives?
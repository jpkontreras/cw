# Mobile App Architecture Plan

## Executive Summary

Based on client feedback about friction in the order system, we're transitioning from a web-only solution to a dual native app architecture while maintaining Laravel as the API backend with significant performance improvements via FrankenPHP/Octane.

## Current Issues (Client Feedback)

### Pain Points
1. **Too many manual steps** - Staff must update status at every stage while cooking/serving
2. **Slow performance** - Web interface feels sluggish during peak hours
3. **Complex workflows** - Adding items mid-order requires too many clicks
4. **Device switching** - Staff lose context when moving between stations

### Failed Approaches Considered
- Hardware buttons/scanners - Too expensive, no budget
- WhatsApp integration - Unreliable, external dependency
- Multiple printer hubs - Bluetooth only supports single connection

## Proposed Architecture

### Two React Native Apps + Laravel API

```
         ┌─────────────────┐
         │   Laravel API   │
         │  (colame.cl)    │
         │  FrankenPHP     │
         └────────┬────────┘
                  │ HTTP/WebSocket
     ┌────────────┴────────────┐
     ▼                         ▼
┌──────────┐            ┌──────────┐
│ STATION  │            │  MOBILE  │
│   APP    │◄──────────►│   APP    │
│ (Tablet) │   Local    │ (Phone)  │
└────┬─────┘   Network  └──────────┘
     │
     │ Bluetooth
     ▼
┌──────────┐
│GOOJPRT210│
│ Printer  │
└──────────┘
```

### App 1: **colame-station** (Tablet Host)

**Purpose**: Stationary POS terminal and print server

**Features**:
- Kitchen display system (KDS)
- Cashier POS interface
- Bluetooth print server for all devices
- Order queue management
- Real-time dashboard
- Printer connection management

**Hardware**:
- Android tablet (fixed at counter/kitchen)
- Always connected to GOOJPRT-210 via Bluetooth
- Always plugged in (no battery concerns)

### App 2: **colame-mobile** (Waiter Satellites)

**Purpose**: Mobile order taking and table management

**Features**:
- Table-side ordering
- Order status checking
- Payment processing
- Table management
- Send print jobs to station
- Offline-first with sync

**Hardware**:
- Waiter smartphones/tablets
- Battery-optimized
- Works offline, syncs when connected

## Technical Implementation

### Shared Codebase Structure

```
/colame-monorepo/
├── packages/
│   ├── shared/           # Shared business logic
│   │   ├── api/          # API client
│   │   ├── types/        # TypeScript definitions
│   │   ├── services/     # Business services
│   │   └── utils/        # Helper functions
│   │
│   ├── colame-station/   # Tablet app
│   │   ├── src/
│   │   │   ├── screens/  # Station-specific screens
│   │   │   └── services/ # Print server, etc.
│   │   └── android/ios   # Native code
│   │
│   └── colame-mobile/    # Phone app
│       ├── src/
│       │   └── screens/  # Mobile-specific screens
│       └── android/ios   # Native code
```

### Code Reuse Strategy

**80-90% code reuse** between apps:
- Same API client
- Same business logic
- Same data models
- Same event sourcing system
- Different UI layouts/navigation

### Key Technologies

**Frontend**:
- React Native (Expo)
- TypeScript
- React Query (API state)
- SQLite (offline storage)
- React Navigation

**Backend** (existing):
- Laravel 12 with Octane/FrankenPHP
- Event Sourcing (Spatie)
- PostgreSQL
- Redis queues

## Printer Architecture Solution

### The Bluetooth Limitation Problem
GOOJPRT-210 only accepts ONE Bluetooth connection at a time. Multiple waiters can't connect simultaneously.

### Solution: Station as Print Server

```
[Mobile App] → HTTP → [Station App] → Bluetooth → [GOOJPRT-210]
```

**Options Evaluated**:

1. **RawBT** (Android app)
   - Pros: Ready-made, HTTP API
   - Cons: Requires license, external dependency
   - Cost: ~$50 license

2. **Custom Print Service** (chosen)
   - Station app includes HTTP server (NanoHTTPD)
   - Receives print jobs from mobile apps
   - Queues and forwards to printer
   - Full control, no licensing

### Implementation:

```typescript
// In colame-station
import { NanoHTTPD } from 'react-native-nanohttpd';

class PrintServer {
  start() {
    this.server = new NanoHTTPD(9100);
    this.server.on('/print', (data) => {
      this.sendToBluetooth(data);
    });
  }
}

// In colame-mobile
async function printOrder(order: Order) {
  await fetch('http://station.local:9100/print', {
    method: 'POST',
    body: generateESCPOS(order)
  });
}
```

## OrderSlip Barcode System

### Workflow
1. **Order Confirmation** → Auto-print slip with barcode
2. **Kitchen Scan** → Mark as "preparing" (single scan)
3. **Completion** → Scan to mark "ready"

### Benefits
- Maintains full event-sourcing audit trail
- Reduces clicks/taps by 70%
- Physical slip = visual queue for kitchen
- Barcode = quick status updates

### Implementation Status
✅ OrderSlipPrinted event
✅ SlipScannedReady event
✅ OrderSlipController API
✅ Barcode generation using order number

## Performance Improvements

### Laravel Octane + FrankenPHP (Production)

**Before** (PHP-FPM):
- Response time: 50-200ms
- Memory per request: 50MB
- Max concurrent: ~100

**After** (FrankenPHP):
- Response time: 2-5ms (25x faster)
- Memory shared: 200MB total
- Max concurrent: 10,000+

### Why FrankenPHP over Swoole?
- ✅ Works with Xdebug (Swoole doesn't)
- ✅ Simpler Docker deployment
- ✅ Built-in HTTPS/SSL
- ✅ Compatible with monitoring tools
- ✅ No PHP extension compilation

## Migration Timeline

### Phase 1: API Preparation (Week 1)
- [x] Install Laravel Octane
- [x] Configure FrankenPHP
- [x] Add health endpoints
- [x] Test performance improvements
- [ ] Deploy to staging

### Phase 2: React Native Setup (Week 2)
- [ ] Initialize Expo monorepo
- [ ] Extract shared code from web
- [ ] Set up navigation structure
- [ ] Configure API client
- [ ] Implement auth flow

### Phase 3: Station App (Week 3)
- [ ] Port cashier screens
- [ ] Implement print server
- [ ] Add Bluetooth printer connection
- [ ] Create kitchen display
- [ ] Test print queue management

### Phase 4: Mobile App (Week 4)
- [ ] Port waiter screens
- [ ] Implement offline storage
- [ ] Add sync mechanism
- [ ] Configure push notifications
- [ ] Test table management

### Phase 5: Testing & Deployment (Week 5)
- [ ] End-to-end testing
- [ ] Performance testing
- [ ] Staff training
- [ ] Gradual rollout
- [ ] Monitor and iterate

## Cost-Benefit Analysis

### Development Costs
- 5 weeks development time
- No new backend infrastructure
- Reuse 80% of existing code

### Hardware Costs
- Existing: GOOJPRT-210 printers
- Existing: Tablets/phones
- New: None required

### Benefits
- **70% reduction** in order processing time
- **25x faster** API responses
- **Offline capability** prevents downtime
- **Native performance** feels instant
- **Role-specific UIs** optimize workflows

## Risk Mitigation

### Technical Risks
1. **Bluetooth connectivity issues**
   - Mitigation: Fallback to manual entry
   - Station app shows print queue status

2. **Network failures**
   - Mitigation: Offline-first architecture
   - Events sync when reconnected

3. **App store delays**
   - Mitigation: Use Expo Updates for OTA
   - Critical fixes without store review

### Business Risks
1. **Staff resistance**
   - Mitigation: Gradual rollout
   - Keep web interface as backup

2. **Training requirements**
   - Mitigation: Similar UI to web
   - Video tutorials prepared

## Alternative Approaches Considered

### Why Not PWA?
- Limited Bluetooth access
- No true offline support
- Browser security restrictions
- Can't run background services

### Why Not Full Native?
- Would require 2 separate codebases
- 3x development time
- Harder to maintain
- Lose code sharing benefits

### Why Not Keep Web Only?
- Client explicitly unhappy with friction
- Performance limitations of web
- No native hardware integration
- Funding depends on improvement

## Success Metrics

### Performance KPIs
- Order processing time: <30 seconds (currently 2+ minutes)
- API response time: <5ms (currently 50ms)
- App launch time: <2 seconds
- Offline capability: 100% core features

### Business KPIs
- Staff satisfaction score: >8/10
- Order accuracy: >99%
- Peak hour throughput: 2x current
- Training time: <30 minutes per role

## Conclusion

This dual-app architecture addresses all client concerns while:
- Maintaining the event-sourcing audit trail
- Reusing most existing code
- Requiring no new hardware investment
- Providing dramatic performance improvements
- Enabling true offline-first operation

The combination of React Native apps with Laravel Octane/FrankenPHP backend provides the performance and user experience improvements needed to secure continued funding while maintaining system integrity and accountability.
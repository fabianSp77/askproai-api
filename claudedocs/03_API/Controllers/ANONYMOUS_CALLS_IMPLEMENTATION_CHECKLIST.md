# Implementation Checklist: Anonymous Calls Fix
**Target:** Reduce failures from 72% to <30% in Week 1

---

## PHASE 1: EMERGENCY FIXES (This Week)

### Day 1: Fix #1 - Proactive Name Collection
**Effort:** 2 hours | **Impact:** -40% failures

- [ ] **Task 1.1:** Update Retell agent greeting prompt
  ```
  Old: "Willkommen bei Ask Pro, möchten Sie einen Termin buchen?"
  New: "Willkommen bei Ask Pro. Bevor wir beginnen, darf ich Ihren Namen haben?"
  ```
  - [ ] Access Retell AI dashboard
  - [ ] Navigate to agent configuration
  - [ ] Update prompt text
  - [ ] Save changes
  - **Files:** Retell Dashboard → Agent Settings

- [ ] **Task 1.2:** Add follow-up prompt after name
  ```
  New: "Danke {name}! Wie kann ich Ihnen heute helfen?"
  ```
  - [ ] Configure name variable interpolation
  - [ ] Test with sample conversation
  - **Files:** Retell Dashboard → Conversation Flow

- [ ] **Task 1.3:** Deploy and test
  - [ ] Make 3 test calls with anonymous number
  - [ ] Verify name is collected immediately
  - [ ] Check customer record is created
  - [ ] Validate appointment can be created
  - **Testing:** Use staging environment first

- [ ] **Task 1.4:** Monitor initial results
  - [ ] Set up dashboard for new metric: "name_collected_first"
  - [ ] Alert if success rate < 80%
  - **Files:** Add to monitoring dashboard

**Success Criteria:**
- [ ] Name collected in first 5 seconds
- [ ] 80%+ of anonymous calls get customer_id
- [ ] Zero code changes required (prompt only)

---

### Day 2: Fix #2 - Immediate Temporary Customer Creation
**Effort:** 4 hours | **Impact:** -25% failures

- [ ] **Task 2.1:** Update `checkCustomer()` to create temp customers
  - [ ] Open `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php`
  - [ ] Find `checkCustomer()` method (line 48)
  - [ ] Add anonymous customer creation logic:
  ```php
  // AFTER line 76 (where customer search happens)
  if (!$customer && $phoneNumber === 'anonymous') {
      $customer = Customer::create([
          'name' => 'Anrufer vom ' . now()->format('d.m.Y H:i'),
          'phone' => 'anonymous_' . $callId,
          'company_id' => $companyId,
          'branch_id' => $branchId,
          'source' => 'anonymous_call',
          'status' => 'temporary',
          'notes' => 'Bitte bei nächstem Kontakt Namen erfragen',
          'metadata' => json_encode([
              'call_id' => $callId,
              'created_reason' => 'anonymous_call_temp',
              'awaiting_verification' => true
          ])
      ]);

      // Link to call immediately
      Call::where('retell_call_id', $callId)
          ->update(['customer_id' => $customer->id]);

      Log::info('✅ Temporary customer created for anonymous call', [
          'customer_id' => $customer->id,
          'call_id' => $callId
      ]);
  }
  ```
  - [ ] Save file
  - **Files:** `app/Http/Controllers/Api/RetellApiController.php`

- [ ] **Task 2.2:** Update `ensureCustomer()` fallback
  - [ ] Open `/var/www/api-gateway/app/Services/Retell/AppointmentCreationService.php`
  - [ ] Find `ensureCustomer()` method (line 488)
  - [ ] Update to always return customer (never null):
  ```php
  // AFTER line 558 (after all customer search attempts)
  if (!$customer) {
      // Last resort: Create temporary customer
      $customer = Customer::create([
          'name' => 'Anrufer vom ' . now()->format('d.m.Y H:i'),
          'phone' => $call->from_number ?? 'anonymous_' . $call->retell_call_id,
          'company_id' => $call->company_id,
          'branch_id' => $call->branch_id,
          'source' => 'retell_fallback',
          'status' => 'temporary'
      ]);

      Log::warning('🆘 Emergency customer created', [
          'call_id' => $call->id,
          'customer_id' => $customer->id
      ]);
  }

  // Ensure customer is linked
  $this->callLifecycle->linkCustomer($call, $customer);
  return $customer; // NEVER return null
  ```
  - [ ] Save file
  - **Files:** `app/Services/Retell/AppointmentCreationService.php`

- [ ] **Task 2.3:** Add customer merge job (post-call)
  - [ ] Create new job: `php artisan make:job MergeTempCustomerJob`
  - [ ] Implement logic:
  ```php
  public function handle() {
      $tempCustomer = $this->tempCustomer;

      // Search for existing customer with same phone/name
      $existing = Customer::where('company_id', $tempCustomer->company_id)
          ->where('status', 'active')
          ->where(function($q) use ($tempCustomer) {
              $q->where('phone', $tempCustomer->phone)
                ->orWhere('name', $tempCustomer->name);
          })
          ->first();

      if ($existing) {
          // Merge: Transfer all relations
          Appointment::where('customer_id', $tempCustomer->id)
              ->update(['customer_id' => $existing->id]);
          Call::where('customer_id', $tempCustomer->id)
              ->update(['customer_id' => $existing->id]);

          $tempCustomer->delete();
          Log::info('✅ Temp customer merged', [
              'temp_id' => $tempCustomer->id,
              'merged_into' => $existing->id
          ]);
      } else {
          // Promote to permanent
          $tempCustomer->update(['status' => 'active']);
          Log::info('✅ Temp customer promoted', [
              'customer_id' => $tempCustomer->id
          ]);
      }
  }
  ```
  - [ ] Save file
  - **Files:** `app/Jobs/MergeTempCustomerJob.php`

- [ ] **Task 2.4:** Dispatch merge job after call_analyzed
  - [ ] Open `/var/www/api-gateway/app/Http/Controllers/RetellWebhookController.php`
  - [ ] Find `call_analyzed` handler (line 248)
  - [ ] Add after name extraction (line 315):
  ```php
  // After customer linking logic
  if ($call->customer && $call->customer->status === 'temporary') {
      dispatch(new MergeTempCustomerJob($call->customer))
          ->delay(now()->addMinutes(5)); // Give time for any updates
  }
  ```
  - [ ] Save file
  - **Files:** `app/Http/Controllers/RetellWebhookController.php`

- [ ] **Task 2.5:** Test temporary customer flow
  - [ ] Make anonymous test call
  - [ ] Verify temp customer created immediately
  - [ ] Verify appointment can be created
  - [ ] Wait 5 minutes, verify merge job runs
  - [ ] Check logs for merge/promote result

**Success Criteria:**
- [ ] Every call gets customer_id within 3 seconds
- [ ] Appointments never blocked by missing customer
- [ ] Temp customers merged/promoted after call
- [ ] Zero duplicate customers created

---

### Day 3: Fix #3 - Manual Review Queue
**Effort:** 4 hours | **Impact:** -7% failures (safety net)

- [ ] **Task 3.1:** Add review fields to calls table
  ```bash
  php artisan make:migration add_review_fields_to_calls_table
  ```
  - [ ] Add columns:
  ```php
  $table->boolean('requires_manual_review')->default(false);
  $table->string('review_reason')->nullable();
  $table->enum('review_priority', ['low', 'normal', 'high'])->default('normal');
  $table->timestamp('reviewed_at')->nullable();
  $table->unsignedBigInteger('reviewed_by')->nullable();
  ```
  - [ ] Run migration: `php artisan migrate`
  - **Files:** `database/migrations/YYYY_MM_DD_add_review_fields_to_calls_table.php`

- [ ] **Task 3.2:** Flag calls for review in webhook
  - [ ] Open `/var/www/api-gateway/app/Http/Controllers/RetellWebhookController.php`
  - [ ] Add after call analysis (line 330):
  ```php
  // Flag anonymous calls without customer for review
  if ($call->from_number === 'anonymous' &&
      (!$call->customer_id || $call->customer->status === 'temporary')) {

      $priority = $call->appointment_made ? 'high' : 'normal';

      $call->update([
          'requires_manual_review' => true,
          'review_reason' => 'anonymous_call_verification',
          'review_priority' => $priority
      ]);

      // Create admin notification
      \App\Models\Notification::create([
          'type' => 'call_review_required',
          'title' => 'Anonymous Call Needs Review',
          'message' => "Call #{$call->id} requires customer verification",
          'data' => json_encode([
              'call_id' => $call->id,
              'priority' => $priority,
              'reason' => 'anonymous_caller'
          ]),
          'priority' => $priority
      ]);
  }
  ```
  - [ ] Save file
  - **Files:** `app/Http/Controllers/RetellWebhookController.php`

- [ ] **Task 3.3:** Create Filament review page
  - [ ] Open `/var/www/api-gateway/app/Filament/Pages/CallReviewQueue.php` (create if needed)
  - [ ] Add table with filters:
  ```php
  public static function table(Table $table): Table {
      return $table
          ->query(Call::where('requires_manual_review', true)
              ->whereNull('reviewed_at')
              ->orderBy('review_priority', 'desc')
              ->orderBy('created_at', 'desc'))
          ->columns([
              TextColumn::make('id'),
              TextColumn::make('created_at')->dateTime(),
              BadgeColumn::make('review_priority'),
              TextColumn::make('from_number'),
              TextColumn::make('customer_name'),
              BooleanColumn::make('appointment_made'),
          ])
          ->actions([
              Action::make('review')
                  ->action(fn (Call $record) => redirect()->route('filament.resources.calls.edit', $record))
          ]);
  }
  ```
  - [ ] Add to sidebar navigation
  - [ ] Save file
  - **Files:** `app/Filament/Pages/CallReviewQueue.php`

- [ ] **Task 3.4:** Add review action to CallResource
  - [ ] Open `/var/www/api-gateway/app/Filament/Resources/CallResource.php`
  - [ ] Add bulk action:
  ```php
  BulkAction::make('mark_reviewed')
      ->label('Mark as Reviewed')
      ->action(function (Collection $records) {
          $records->each->update([
              'reviewed_at' => now(),
              'reviewed_by' => auth()->id(),
              'requires_manual_review' => false
          ]);
      })
      ->requiresConfirmation()
      ->deselectRecordsAfterCompletion()
  ```
  - [ ] Save file
  - **Files:** `app/Filament/Resources/CallResource.php`

- [ ] **Task 3.5:** Test review queue
  - [ ] Make test anonymous call
  - [ ] Verify appears in review queue
  - [ ] Open call details
  - [ ] Link to correct customer manually
  - [ ] Mark as reviewed
  - [ ] Verify removed from queue

**Success Criteria:**
- [ ] All problem calls flagged automatically
- [ ] Review queue accessible to admins
- [ ] One-click manual linking
- [ ] Reviewed calls removed from queue
- [ ] Metrics tracked (review time, resolution rate)

---

### Day 4: Testing & Validation
**Effort:** 3 hours

- [ ] **Task 4.1:** End-to-end testing
  - [ ] Test Case 1: Anonymous call, name provided
    - [ ] Make call with anonymous number
    - [ ] Provide name when asked
    - [ ] Request appointment
    - [ ] Verify: customer created, appointment created, no review needed
  - [ ] Test Case 2: Anonymous call, no name
    - [ ] Make call with anonymous number
    - [ ] Don't provide name
    - [ ] Request appointment
    - [ ] Verify: temp customer created, appointment created, flagged for review
  - [ ] Test Case 3: Anonymous call, early hangup
    - [ ] Make call with anonymous number
    - [ ] Hang up immediately after greeting
    - [ ] Verify: temp customer created, flagged for review
  - [ ] Test Case 4: Regular call (non-anonymous)
    - [ ] Make call with normal number
    - [ ] Verify: customer linked, appointment created, no issues

- [ ] **Task 4.2:** Load testing
  - [ ] Simulate 10 concurrent anonymous calls
  - [ ] Verify no race conditions
  - [ ] Check database consistency
  - [ ] Monitor error rates

- [ ] **Task 4.3:** Review monitoring
  - [ ] Check logs for errors
  - [ ] Verify metrics being collected
  - [ ] Test alert system
  - [ ] Validate dashboard accuracy

**Success Criteria:**
- [ ] All 4 test cases pass
- [ ] Zero errors in logs
- [ ] Metrics accurate
- [ ] Performance acceptable (<500ms response time)

---

### Day 5: Production Deployment
**Effort:** 2 hours

- [ ] **Task 5.1:** Pre-deployment checklist
  - [ ] All tests pass
  - [ ] Code reviewed by lead
  - [ ] Database migrations tested
  - [ ] Rollback plan documented
  - [ ] Monitoring alerts configured

- [ ] **Task 5.2:** Deploy to production
  - [ ] Create backup of database
  - [ ] Run migrations: `php artisan migrate --force`
  - [ ] Clear cache: `php artisan cache:clear`
  - [ ] Restart queue workers
  - [ ] Update Retell agent prompt (live)

- [ ] **Task 5.3:** Post-deployment monitoring (first hour)
  - [ ] Monitor error rates (target: <1%)
  - [ ] Check customer creation rate
  - [ ] Verify appointment creation working
  - [ ] Watch review queue for issues
  - [ ] Test with real anonymous call

- [ ] **Task 5.4:** Communication
  - [ ] Notify team of deployment
  - [ ] Share new metrics dashboard
  - [ ] Document any issues found
  - [ ] Schedule follow-up review (Day 7)

**Success Criteria:**
- [ ] Zero critical errors
- [ ] Anonymous call success rate >70% (up from 28%)
- [ ] All appointments created successfully
- [ ] Review queue manageable (<10 pending)

---

## SUCCESS METRICS (Track Daily)

### Day 1 Baseline
- [ ] Anonymous calls today: _____
- [ ] Without customer_id: _____
- [ ] Success rate: _____%
- [ ] Revenue lost: €_____

### Day 5 Results
- [ ] Anonymous calls today: _____
- [ ] Without customer_id: _____
- [ ] Success rate: _____%
- [ ] Revenue recovered: €_____

### Week 1 Summary
- [ ] Total anonymous calls: _____
- [ ] Success rate improvement: _____%
- [ ] Appointments created: _____
- [ ] Revenue impact: €_____
- [ ] Manual review time: _____ hours

---

## ROLLBACK PLAN (If Needed)

If success rate drops or errors spike:

1. **Immediate:**
   - [ ] Revert Retell agent prompt
   - [ ] Disable temp customer creation
   - [ ] Stop merge job queue

2. **Investigation:**
   - [ ] Check error logs
   - [ ] Review failed calls
   - [ ] Identify root cause

3. **Fix Forward:**
   - [ ] Apply hotfix
   - [ ] Test in staging
   - [ ] Re-deploy with fix

---

## PHASE 2 PREVIEW (Next 2 Weeks)

Coming after Phase 1 succeeds:
- [ ] SMS follow-up system
- [ ] Retry name collection logic
- [ ] Confidence-based appointment confirmation
- [ ] Enhanced duplicate detection

Target: <10% failure rate

---

## SIGN-OFF

**Development Lead:** _____________________ Date: _____
**Product Manager:** _____________________ Date: _____
**QA Lead:** _____________________ Date: _____

**Status:** 🟡 Ready for execution
**Priority:** 🔴 CRITICAL
**Est. Completion:** 2025-10-18

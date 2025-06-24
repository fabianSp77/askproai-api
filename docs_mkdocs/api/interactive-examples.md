# Interactive API Examples

## Try It Out!

These examples can be copied and run directly in your terminal.

### 1. Authentication

```bash
# Get API Token
curl -X POST https://api.askproai.de/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your@email.com",
    "password": "your-password"
  }'
```

### 2. Create Appointment

```bash
# Create a new appointment
curl -X POST https://api.askproai.de/api/v2/appointments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Company-ID: 1" \
  -d '{
    "service_id": 1,
    "staff_id": 5,
    "customer": {
      "name": "Max Mustermann",
      "phone": "+49 30 123456",
      "email": "max@example.com"
    },
    "start_time": "2025-06-25T14:00:00Z",
    "branch_id": 1,
    "notes": "First time customer"
  }'
```

### 3. JavaScript SDK Example

```javascript
// Using our JavaScript SDK
import { AskProAI } from '@askproai/sdk';

const client = new AskProAI({
  apiKey: 'YOUR_API_KEY',
  companyId: 1
});

// Check availability
const slots = await client.availability.check({
  service_id: 1,
  date: '2025-06-25',
  branch_id: 1
});

// Create appointment
const appointment = await client.appointments.create({
  service_id: 1,
  staff_id: slots[0].staff_id,
  start_time: slots[0].time,
  customer: {
    name: 'Max Mustermann',
    phone: '+49 30 123456'
  }
});
```


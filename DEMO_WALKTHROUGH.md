# 🎮 System Demo Walkthrough Script

> **Live Demonstration Guide for Capstone Defense**  
> **Estimated Time**: 8-10 minutes

---

## 📋 Pre-Demo Checklist

- [ ] System running at `http://localhost/dogpound`
- [ ] Database has sample data (seeders loaded)
- [ ] Two browser tabs ready (for real-time demo)
- [ ] Test accounts ready:
  - **Admin**: admin@catarmandogpound.com / password
  - **Adopter**: adopter@test.com / password
- [ ] Dark mode OFF (start with light mode)
- [ ] Browser zoom at 100%

---

## 🎬 DEMO FLOW

### **Step 1: Admin Dashboard (1 min)**

**Open**: `http://localhost/dogpound` → Login as Admin

**Say**: 
> "Let me log in as an Administrator. The dashboard gives me a complete overview of the shelter's operations."

**Show**:
1. **Statistics Cards** — Total animals, available for adoption, treatments this month, revenue
2. **Intake Chart** — Click "Month" tab to show monthly trends
3. **Status Distribution** — Point to the doughnut chart
4. **Quick Glance** — Recent animals and pending adoptions

**Say**:
> "The dashboard updates in real-time. If another staff member adds an animal, I'll see it immediately without refreshing."

---

### **Step 2: Add an Animal (2 min)**

**Navigate**: Click "Animals" in sidebar

**Say**:
> "Let me demonstrate adding a new animal to the system."

**Click**: "Add Animal" button

**Fill Form** (speak while typing):
- **Name**: "Demo" 
- **Type**: Dog
- **Breed**: "Aspin"
- **Gender**: Male
- **Age Group**: Adult
- **Weight**: 8 kg
- **Intake Status**: Stray
- **Upload Image**: (if prepared, or skip)

**Say**:
> "All input is validated. The system prevents invalid data and sanitizes input to prevent security attacks."

**Click**: Save

**Say**:
> "The animal is now registered and marked as 'Available' for adoption. Notice the toast notification confirming the action."

---

### **Step 3: Adopter Experience (2 min)**

**Say**:
> "Now let me show you the adopter's perspective."

**Action**: Open new tab or logout → Login as Adopter

**Say**:
> "Adopters see a simplified view. They can browse available animals but cannot access admin features."

**Navigate**: Click on the animal you just added → View details

**Say**:
> "Each animal has a detailed profile. The adopter can submit an adoption request from here."

**Click**: "Adopt Me" or "Submit Adoption Request"

**Fill Form**:
- **Reason**: "I want to give Demo a loving home"
- **Address**: "Catarman, Northern Samar"

**Click**: Submit

**Say**:
> "The request is now pending. The adopter can track it in 'My Requests' page and can cancel if needed."

---

### **Step 4: Process Adoption as Admin (2 min)**

**Action**: Switch to Admin tab or login as Admin

**Navigate**: Adoptions page

**Say**:
> "Back as Admin, I can see all pending requests. Let me process this adoption."

**Click**: View/Process the adoption request

**Show** the interview/seminar scheduling options:

**Say**:
> "The workflow goes: Pending → Interview Scheduled → Seminar Scheduled → Approved → Completed. This ensures proper screening before adoption."

**Action**: Schedule Interview → then Approve the request

**Say Important**:
> "Watch what happens when I approve. The system automatically:
> 1. Sets the animal status to 'Reserved'
> 2. Rejects all other pending requests for this animal
> 3. Generates an invoice for the adoption fee"

**Click**: Approve

**Point out** the toast notifications and status changes

---

### **Step 5: Record Payment & Generate PDF (1.5 min)**

**Navigate**: Billing page

**Say**:
> "The invoice was auto-generated. Let me record the payment."

**Click**: View the invoice → Record Payment

**Fill**:
- **Amount**: Full amount (₱500 for dog)
- **Payment Method**: GCash

**Click**: Submit

**Say**:
> "Payment is recorded. Now let me generate a PDF report."

**Navigate**: Reports section → Click "Generate Report"

**Show**: PDF Preview modal

**Say**:
> "The system uses jsPDF to generate reports client-side. Users can preview before downloading or printing."

---

### **Step 6: Medical Records - Euthanasia Feature (1 min)**

**Navigate**: Medical Records page

**Say**:
> "Let me show a special feature in Medical Records."

**Click**: Add Medical Record

**Fill**:
- **Animal**: Select any animal
- **Diagnosis Type**: Euthanasia
- **Notes**: "Humane euthanasia due to terminal illness"

**Say**:
> "When we record a Euthanasia diagnosis, watch what happens..."

**Click**: Save

**Say**:
> "The system automatically updates the animal's status to 'Deceased'. This ensures data consistency without manual updates."

---

### **Step 6b: Auto-Inactive Users (30 sec - Optional)**

**Say**:
> "The system also has automatic user management. When an Admin loads the Dashboard, it silently checks for inactive Adopter accounts."

**Explain**:
> "If an Adopter hasn't logged in for 30 or more days, the system automatically marks their account as Inactive. This keeps the user base clean without manual intervention. Admins can still reactivate accounts if needed."

*Note: You don't need to demo this visually - just mention it as a feature.*

---

### **Step 7: Real-Time Updates (1 min)**

**Say**:
> "Let me demonstrate the real-time synchronization."

**Action**: Open two browser tabs side by side (both as Admin)

**In Tab 1**: Navigate to Animals page
**In Tab 2**: Navigate to Animals page

**Say**:
> "Both tabs show the same data. Watch Tab 2 when I make a change in Tab 1."

**In Tab 1**: Change an animal's status or add a new animal

**Point to Tab 2**:
> "See? Tab 2 updated automatically without refreshing. This is Server-Sent Events in action."

---

### **Step 8: Responsive Design & Dark Mode (30 sec)**

**Say**:
> "Finally, the system is fully responsive."

**Action**: Resize browser window to mobile size OR use DevTools mobile view

**Show**: Cards stack vertically, tables become scrollable

**Say**:
> "Staff can use this on tablets while walking through the facility."

**Click**: Dark mode toggle (in Settings or header)

**Say**:
> "We also support dark mode for user preference. The theme persists across sessions."

---

## ✅ Demo Complete!

**Say**:
> "That concludes our live demonstration. The system covers animal intake, adoption processing, medical records, billing, inventory, and provides real-time synchronization with proper security controls."

---

## 🆘 Backup Plans (If Something Goes Wrong)

| Issue | Backup |
|-------|--------|
| **Server not running** | Have screenshots ready |
| **Database empty** | Run seeders.sql quickly |
| **Login fails** | Check if session expired, re-login |
| **PDF won't generate** | Show the button exists, explain jsPDF |
| **Real-time not working** | Explain SSE concept, show it works on refresh |

---

## 💡 Tips for Smooth Demo

1. **Speak while you type** — Don't silently fill forms
2. **Point at the screen** — Guide the panel's attention
3. **Mention security features** — "Notice the input validation..."
4. **Acknowledge delays** — "The system is processing..." if slow
5. **Have confidence** — You built this, you know it!

---

**Good luck! You've got this! 🍀**

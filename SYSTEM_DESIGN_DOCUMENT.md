# System Design Document

This document provides detailed logic flow diagrams for the major functions of the Catarman Dog Pound Management System, using standard flowchart symbols.

**Legend:**
- `( Text )` : Start / End (Terminator)
- `[ Text ]` : Process / Action
- `/ Text /` : Input / Output
- `< Text >` : Decision / Condition
- `[( Text )]`: Database Operation

## 0. System Architecture

### High-Level Overview
```ascii
+-----------------------------------------------------------------------+
|                   USER BROWSER (Frontend - SPA)                       |
+-----------------------------------------------------------------------+
|                                                                       |
|   +-------------+       +-------------+       +-------------------+   |
|   |  index.html | ----> |   App.js    | ----> |    Router.js      |   |
|   +-------------+       +-------------+       +-------------------+   |
|                                                         |             |
|                                                         v             |
|   +-------------------+                       +-------------------+   |
|   |   Components /    | <-------------------- |   Page Rendering  |   |
|   |      Pages        |                       +-------------------+   |
|   +-------------------+                                 |             |
|            |                                            |             |
|            v                                            v             |
|   +-------------------+                       +-------------------+   |
|   |  User Actions     |                       |      API.js       |   |
|   | (Clicks, Forms)   | --------------------> |  (HTTP Client)    |   |
|   +-------------------+                       +-------------------+   |
|                                                         |             |
+---------------------------------------------------------|-------------+
                                                          |
                                                  HTTP Requests
                                           (GET, POST, PUT, DELETE)
                                                          |
                                                          v
+-----------------------------------------------------------------------+
|                      SERVER (Backend - PHP)                           |
+-----------------------------------------------------------------------+
|                                                                       |
|   +-------------------+                                               |
|   |  public/index.php | <------------------ (Entry Point)             |
|   +-------------------+                                               |
|            |                                                          |
|            v                                                          |
|   +-------------------+                                               |
|   |   bootstrap.php   |                                               |
|   +-------------------+                                               |
|            |                                                          |
|            v                                                          |
|   +-------------------+       +-------------------+                   |
|   |    Router.php     | <---- |     Middleware    |                   |
|   | (Dispatch Routes) |       | (Auth, CORS, etc) |                   |
|   +-------------------+       +-------------------+                   |
|            |                                                          |
|            v                                                          |
|   +-------------------+                                               |
|   |    Controllers    |                                               |
|   | (Logic Handlers)  |                                               |
|   +-------------------+                                               |
|            |                                                          |
|            v                                                          |
|   +-------------------+                                               |
|   |      Models       |                                               |
|   | (Data Access Obj) |                                               |
|   +-------------------+                                               |
|            |                                                          |
+------------|----------------------------------------------------------+
             |
        SQL Queries   
             |
             v
+-------------------------+
|        DATABASE         |
|        (MySQL)          |
+-------------------------+
```

### Detailed Data Flow - Example: Login
```ascii
User
 |
 v
[Client] Login Page (Enter Credentials)
 |
 v
[Client] App.js -> Auth.login() -> API.post('/auth/login')
 |
 v
[Server] public/index.php
 |
 v
[Server] Router.php (Matches POST /auth/login)
 |
 v
[Server] AuthController::login()
 |
 v
[Server] User Model (Validate Credentials)
 |
 v
[Database] SELECT * FROM Users WHERE email = ...
 |
 v
[Server] Generate JWT Token
 |
 v
[Server] Return JSON Response { token: "..." }
 |
 v
[Client] API.js receives response
 |
 v
[Client] Auth.js stores token
 |
 v
[Client] Router.navigate('/dashboard')
```

---

## 1. Authentication Module

### Login Flow (`POST /auth/login`)
```ascii
      ( Start )
           |
           v
      / Credentials /
           |
           v
      < Rate Limit > --(Exceeded)--> [ Return 429 ]
      <     OK?    >       |
           | (Yes)         v
           v            ( End )
      < Valid Input? > --(No)--> [ Return 400 ] --> ( End )
           | (Yes)
           v
    [( Find User    )]
    [( by Email/User)]
           |
           v
      < User Found? > --(No)--> [ Log Failed Attempt ] --> [ Return 401 ] --> ( End )
           | (Yes)
           v
      < Verify Pass > --(No)--> [ Log Failed Attempt ] --> [ Return 401 ] --> ( End )
           | (Yes)
           v
      < Is Active?  > --(No)--> [ Return 403 ] --> ( End )
           | (Yes)
           v
    [ Generate Tokens ]
    [ (Access/Refresh)]
           |
           v
    [( Log Activity )]
           |
           v
      / Return JSON  /
      / (User+Token) /
           |
           v
        ( End )
```

### Registration Flow (`POST /auth/register`)
```ascii
       ( Start )
           |
           v
       / User Data /
           |
           v
       < Valid Input? > --(No)--> [ Return 400 ] --> ( End )
           | (Yes)
           v
     [( Check Email/ )]
     [(   Username   )]
           |
           v
       < Duplicates? > --(Yes)--> [ Return 409 ] --> ( End )
           | (No)
           v
     [ Hash Password ]
           |
           v
     [( Insert User  )]
     [( Role:Adopter )]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Token Refresh Flow (`POST /auth/refresh`)
```ascii
       ( Start )
           |
           v
      / Refresh Token /
           |
           v
      <   Token    >
      <  Valid?    > --(No/Expired)--> [ Return 401 ] --> ( End )
           | (Yes)
           v
      <  Is Type   > --(No)--> [ Return 401 ] --> ( End )
      < 'Refresh'? >
           | (Yes)
           v
     [( Fetch User )]
           |
           v
      <   Found?   > --(No)--> [ Return 401 ] --> ( End )
           | (Yes)
           v
      < Account    >
      < Active?    > --(No)--> [ Return 403 ] --> ( End )
           | (Yes)
           v
     [ Generate New ]
     [ Access Token ]
           |
           v
      / Return JSON /
      / (New Token) /
           |
           v
        ( End )
```

### Logout Flow (`POST /auth/logout`)
```ascii
       ( Start )
           |
           v
      / Auth Header /
           |
           v
      <   Token    > --(Invalid)--> [ Return 401 ] --> ( End )
      <  Valid?    >
           | (Yes)
           v
     [( Log Activity )]
     [(   'LOGOUT'   )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Logout All Flow (`POST /auth/logout-all`)
```ascii
       ( Start )
           |
           v
      / Auth Header /
           |
           v
      <   Token    > --(Invalid)--> [ Return 401 ] --> ( End )
      <  Valid?    >
           | (Yes)
           v
     [( Log Activity )]
     [( 'LOGOUT_ALL' )]
           |
           v
     [ Note: JWT is ]
     [ Stateless.   ]
     [ Client must  ]
     [ discard key. ]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

---

## 2. Animal Management Module

### List Animals (`GET /animals`)
```ascii
       ( Start )
           |
           v
      / Filters:    /
      / Type, Status/
           |
           v
     [( Query DB    )]
     [( Apply Filter)]
           |
           v
     [ Count Total  ]
           |
           v
     [( Fetch Page  )]
           |
           v
     [ Add ImageURLs]
           |
           v
      / Return List  /
           |
           v
        ( End )
```

### View Animal (`GET /animals/{id}`)
```ascii
       ( Start )
           |
           v
      / Animal ID /
           |
           v
     [( Fetch Animal )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
     [( Fetch Impound)]
     [( Fetch Med Cnt)]
     [( Fetch Adopt  )]
           |
           v
      / Return Details/
           |
           v
        ( End )
```

### Create Animal (`POST /animals`)
```ascii
       ( Start )
           |
           v
       / Animal Data /
           |
           v
       < Valid Input? > --(No)--> [ Return 400 ] --> ( End )
           | (Yes)
           v
     [( Insert Animal )]
           |
           v
     [( Log Activity  )]
           |
           v
      / Return Created /
      /    Animal      /
           |
           v
        ( End )
```

### Update Status (`PATCH /animals/{id}/status`)
```ascii
       ( Start )
           |
           v
       / New Status /
           |
           v
     [( Fetch Animal )]
           |
           v
       <   Found?   > --(No)--> [ Return 404 ] --> ( End )
           | (Yes)
           v
       < Valid Status? > --(No)--> [ Return 400 ] --> ( End )
           | (Yes)
           v
     [( Update Status )]
           |
           v
     [( Log Activity  )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Impound Animal (`POST /animals/{id}/impound`)
```ascii
       ( Start )
           |
           v
       / Impound Data /
           |
           v
     [( Check Animal )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
     [( Check Record )]
           |
           v
       <   Exists?   > --(Yes)--> [ Return 409 ] --> ( End )
           | (No)
           v
       < Valid Data? > --(No)--> [ Return 400 ] --> ( End )
           | (Yes)
           v
     [( Insert Record )]
           |
           v
     [( Log Activity  )]
           |
           v
      / Return Record  /
           |
           v
        ( End )
```

---

## 3. Adoption Process Module

### Submit Request (`POST /adoptions`)
```ascii
       ( Start )
           |
           v
       / Animal ID /
           |
           v
     [( Fetch Animal )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
       < Available? > --(No)--> [ Return 400 'Unavailable' ] --> ( End )
           | (Yes)
           v
    [( Check Users    )]
    [( Active Requests)]
           |
           v
       < Duplicate? > --(Yes)--> [ Return 400 'Already Requested' ] --> ( End )
           | (No)
           v
    [( Insert Request )]
    [( Status:Pending )]
           |
           v
    [( Log Activity   )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Process Request (`PUT /adoptions/{id}/process`)
```ascii
       ( Start )
           |
           v
       / New Status /
           |
           v
    [( Fetch Request )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
    [ Begin Transaction ]
           |
           v
       < New Status? >
           |
      +----+------------------+------------------+
      |                       |                  |
      v                       v                  v
 [ Interview ]           [ Approved ]       [ Completed ]
 [ Scheduled ]           [ Rejected ]            |
      |                       |                  v
      v                       v           [( Update Animal )]
 [( Update Status )]   [( Update Status )]  [( Status:Adopted)]
 [( Set Date      )]          |                  |
      |                       |                  v
      |                       |           [( Reject Others )]
      |                       |           [( For This Animal)]
      |                       |                  |
      +-----------+-----------+------------------+
                  |
                  v
          [ Commit Transaction ]
                  |
                  v
          [(  Log Activity  )]
                  |
                  v
           / Return Success /
                  |
                  v
               ( End )
```

---

## 4. Medical Module

### Create Record (`POST /medical`)
```ascii
       ( Start )
           |
           v
      / Medical Data /
           |
           v
     [( Verify Animal )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
     [( Verify Vet    )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
     [( Insert Record )]
           |
           v
     [( Log Activity  )]
           |
           v
      / Return Record  /
           |
           v
        ( End )
```

### Upcoming Treatments (`GET /medical/upcoming`)
```ascii
       ( Start )
           |
           v
       / Days Filter /
           |
           v
    [( Query Records )]
           |
           v
    [ Filter Logic:    ]
    [ 1. Has Due Date  ]
    [ 2. Within Range  ]
    [ 3. Animal Active ]
           |
           v
    [ Group By Animal  ]
    [ Select Latest    ]
           |
           v
    / Return List /
           |
           v
        ( End )
```

### Record Feeding (`POST /feeding`)
```ascii
       ( Start )
           |
           v
      / Feeding Data /
      / (Food, Qty)  /
           |
           v
     [( Verify Animal )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
     [( Insert Record )]
           |
           v
     [( Log Activity  )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

---

## 5. Inventory Module

### List Inventory (`GET /inventory`)
```ascii
       ( Start )
           |
           v
      / Filters:    /
      / Category... /
           |
           v
     [( Query Items )]
     [( Apply FLtr  )]
           |
           v
     [ Check Low    ]
     [ Stock/Expiry ]
           |
           v
      / Return List  /
           |
           v
        ( End )
```

### Create Item (`POST /inventory`)
```ascii
       ( Start )
           |
           v
       / Item Data /
           |
           v
      < Valid Input? > --(No)--> [ Return 400 ] --> ( End )
           | (Yes)
           v
     [( Insert Item )]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Item  /
           |
           v
        ( End )
```

### Update Item (`PUT /inventory/{id}`)
```ascii
       ( Start )
           |
           v
      / Update Data /
           |
           v
     [( Fetch Item )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
     [( Update DB  )]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Delete Item (`DELETE /inventory/{id}`)
```ascii
       ( Start )
           |
           v
     [( Fetch Item )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
     [( Delete Item )]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Adjust Stock (`PATCH /inventory/{id}/adjust`)
```ascii
       ( Start )
           |
           v
       / Adjustment /
       /  Details   /
           |
           v
     [( Fetch Item )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
       <  Subtract  > --(No)--> [ Add Stock ] ----------------+
       <    Op?     >                                         |
           | (Yes)                                            |
           v                                                  |
       < Stock <    > --(Yes)--> [ Return 400 ] --> ( End )   |
       < Amount?    >                                         |
           | (No)                                             |
           v                                                  |
     [ Subtract Stock ]                                       |
           |                                                  |
           +--------------------------<-----------------------+
           |
           v
     [( Update DB   )]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Item  /
           |
           v
        ( End )
```

### Alert Logic (`GET /inventory/alerts`)
```ascii
       ( Start )
           |
           v
    [( Query Out of Stock )]
    [(   Qty <= 0        )]
           |
           v
    [( Query Low Stock    )]
    [( Qty <= Reorder Lvl )]
           |
           v
    [( Query Expiring     )]
    [( Date <= Now+30d    )]
           |
           v
    [( Query Expired      )]
    [( Date < Now         )]
           |
           v
      / Return Combined /
      /     JSON        /
           |
           v
        ( End )
```

---

## 6. Billing Module

### List Invoices (`GET /invoices`)
```ascii
       ( Start )
           |
           v
      / Filters:    /
      / Status, Type/
           |
           v
     [( Query Inv.  )]
     [( Join Payer  )]
           |
           v
     [ Calc Balance ]
     [ (Total-Paid) ]
           |
           v
      / Return List  /
           |
           v
        ( End )
```

### Create Invoice (`POST /invoices`)
```ascii
       ( Start )
           |
           v
       / Invoice Data /
           |
           v
     [( Verify Payer )] --(Not Found)--> [ Return 400 ] --> ( End )
           | (Found)
           v
     [( Insert Invoice )]
     [( Status: Unpaid )]
           |
           v
     [( Log Activity  )]
           |
           v
      / Return Invoice /
           |
           v
        ( End )
```

### Record Payment (`POST /payments`)
```ascii
       ( Start )
           |
           v
       / Payment Data /
           |
           v
     [( Fetch Invoice )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
       <   Paid OR    > --(Yes)--> [ Return 400 ] --> ( End )
       <  Cancelled?  >
           | (No)
           v
    [ Begin Transaction ]
           |
           v
    [( Insert Payment  )]
           |
           v
    [( Calculate Total )]
           |
           v
       < Total >=     > --(Yes)--> [( Update Status )]
       < Invoice Amt? >            [(   to 'Paid'   )]
           | (No)                         |
           |                              |
           +--------------<---------------+
           |
           v
    [ Commit Transaction ]
           |
           v
    [( Log Activity   )]
           |
           v
     / Return Success /
           |
           v
        ( End )
```

### Cancel Invoice (`PUT /invoices/{id}/cancel`)
```ascii
       ( Start )
           |
           v
     [( Fetch Invoice )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
      < Is Paid? > --(Yes)--> [ Return 400 ] --> ( End )
           | (No)
           v
      < Has Payments? > --(Yes)--> [ Return 400 ] --> ( End )
           | (No)
           v
     [( Set Status  )]
     [( 'Cancelled' )]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Financial Report (`GET /billing/report`)
```ascii
       ( Start )
           |
           v
      / Date Range /
           |
           v
     [( Query Invoices )]
     [( Sum Billed     )]
           |
           v
     [( Query Payments )]
     [( Sum Collected  )]
           |
           v
     [ Calc Unpaid ]
           |
           v
     [ Group Daily ]
           |
           v
      / Return JSON /
           |
           v
        ( End )
```

---

## 7. User Management Module

### List Users (`GET /users`)
```ascii
       ( Start )
           |
           v
      / Filters:    /
      / Role, Status/
           |
           v
     [( Query Users )]
     [( Apply Filter)]
           |
           v
     [( Count Total )]
           |
           v
     [( Fetch Page  )]
           |
           v
      / Return List  /
           |
           v
        ( End )
```

### Create User (`POST /users`) - Admin Only
```ascii
       ( Start )
           |
           v
       < Is Admin? > --(No)--> [ Return 403 ] --> ( End )
           | (Yes)
           v
       / User Data /
           |
           v
       < Valid Input? > --(No)--> [ Return 400 ] --> ( End )
           | (Yes)
           v
      [( Check Email )] --(Exists)--> [ Return 409 ] --> ( End )
           |
           v
      [ Hash Password ]
           |
           v
     [ Begin Transaction ]
           |
           v
      [( Insert User )]
           |
           v
      < Role == Vet? > --(Yes)--> [( Insert Vet Details )]
           | (No)                        |
           v                             v
      [ Commit Transaction ] <-----------+
           |
           v
      [( Log Activity )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Update User (`PUT /users/{id}`)
```ascii
       ( Start )
           |
           v
      / Update Data /
           |
           v
     [( Fetch User )] --(Not Found)--> [ Return 404 ] --> ( End )
           |
           v
     < Change Email? > --(Yes)--> [( Check Unique )] --(Exists)--> [ Return 409 ]
           | (No)
           v
     [ Build Updates ]
           |
           v
     [ Begin Transaction ]
           |
           v
     [( Update User )]
           |
           v
     < Update Vet? > --(Yes)--> [( Update Vet Details )]
           |
           v
     [ Commit Transaction ]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Soft Delete User (`DELETE /users/{id}`)
```ascii
       ( Start )
           |
           v
     [( Fetch User )]
           |
           v
      < Self Delete? > --(Yes)--> [ Return 400 ] --> ( End )
           | (No)
           v
      < Last Admin?  > --(Yes)--> [ Return 400 ] --> ( End )
           | (No)
           v
     [( Set Deleted )]
     [( Status:Inactive)]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Success /
           |
           v
        ( End )
```

### Profile Update (`PUT /profile`)
```ascii
       ( Start )
           |
           v
      / Profile Data /
           |
           v
      < Valid Input? > --(No)--> [ Return 400 ] --> ( End )
           | (Yes)
           v
     [( Update User )]
           |
           v
      < Is Vet? > --(Yes)--> [( Update Vet Info )]
           |
           v
     [( Log Activity )]
           |
           v
      / Return Profile /
           |
           v
        ( End )
```

---

## 8. Dashboard & Notification Module

### Dashboard Stats (`GET /dashboard/stats`)
```ascii
       ( Start )
           |
           v
    [( Agg. Animals )] (Status, Type counts)
           |
           v
    [( Agg. Adoption)] (Pending, Approved counts)
           |
           v
    [( Agg. Inventory)] (Low Stock, Expiring)
           |
           v
    [( Agg. Medical )] (Upcoming Treatments)
           |
           v
    [( Agg. Finance )] (Unpaid, Monthly Revenue)
           |
           v
      < Is Admin? > --(Yes)--> [( Agg. Users )]
           |
           v
    [( Fetch Charts )] (Intake Trends)
           |
           v
      / Return JSON  /
           |
           v
        ( End )
```

### Fetch Notifications (`GET /notifications`)
```ascii
       ( Start )
           |
           v
    [ Init List [] ]
           |
           v
    [( Query Low Stock )] --> [ Add to List ]
           |
           v
    [( Query Expiring  )] --> [ Add to List ]
           |
           v
    [( Query Pending   )]
    [(   Adoptions     )] --> [ Add to List ]
           |
           v
    [( Query Unpaid    )]
    [(   Invoices      )] --> [ Add to List ]
           |
           v
    [( Query Treatment )]
    [(      Due        )] --> [ Add to List ]
           |
           v
    [ Sort by Time DESC ]
           |
           v
      / Return List /
           |
           v
        ( End )
```

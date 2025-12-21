# Project Structure

```text
Folder PATH listing for volume Acer
Volume serial number is EA1A-CFA5
C:.
|   add_address_column.php
|   add_preferences_column.php
|   check_admin.php
|   check_email_typo.php
|   debug_adoptions.php
|   fix_adoptions_data.php
|   run_dev.ps1
|   start.bat
|   test_animals_db.php
|   test_dashboard_api.php
|   update_dates.php
|   
+---backend
|   |   .htaccess
|   |   debug_notifications.php
|   |   update_db.php
|   |   
|   +---app
|   |   |   bootstrap.php
|   |   |   
|   |   +---api
|   |   |       adoptions.php
|   |   |       animals.php
|   |   |       auth.php
|   |   |       billing.php
|   |   |       dashboard.php
|   |   |       inventory.php
|   |   |       medical.php
|   |   |       notifications.php
|   |   |       users.php
|   |   |       
|   |   +---config
|   |   |       config.php
|   |   |       database.php
|   |   |       
|   |   +---controllers
|   |   |       AdoptionController.php
|   |   |       AnimalController.php
|   |   |       AuthController.php
|   |   |       BaseController.php
|   |   |       BillingController.php
|   |   |       DashboardController.php
|   |   |       InventoryController.php
|   |   |       MedicalController.php
|   |   |       NotificationController.php
|   |   |       UserController.php
|   |   |       
|   |   +---middleware
|   |   |       AuthMiddleware.php
|   |   |       
|   |   +---models
|   |   |       ActivityLog.php
|   |   |       AdoptionRequest.php
|   |   |       Animal.php
|   |   |       FeedingRecord.php
|   |   |       ImpoundRecord.php
|   |   |       Inventory.php
|   |   |       Invoice.php
|   |   |       MedicalRecord.php
|   |   |       Payment.php
|   |   |       Role.php
|   |   |       User.php
|   |   |       Veterinarian.php
|   |   |       
|   |   \---utils
|   |           JWT.php
|   |           Response.php
|   |           Router.php
|   |           Validator.php
|   |           
|   +---logs
|   |       error.log
|   |       
|   \---public
|       |   .htaccess
|       |   add_username_column.php
|       |   check_db_users.php
|       |   check_seed_counts.php
|       |   debug_overdue.php
|       |   debug_overdue_2.php
|       |   debug_profile_manual.php
|       |   debug_user_find.php
|       |   describe_users.php
|       |   index.php
|       |   update_usernames.php
|       |   
|       \---uploads
|           +---animals
|           \---avatars
|                   
+---database
|       schema.sql
|       seeders.sql
|       
\---frontend
    |   index.html
    |   
    +---assets
    |   +---css
    |   |       animations.css
    |   |       components.css
    |   |       layouts.css
    |   |       main.css
    |   |       responsive.css
    |   |       variables.css
    |   |       
    |   +---images
    |   +---js
    |   |   |   api.js
    |   |   |   app.js
    |   |   |   auth.js
    |   |   |   router.js
    |   |   |   store.js
    |   |   |   utils.js
    |   |   |   
    |   |   +---components
    |   |   |       Card.js
    |   |   |       Charts.js
    |   |   |       DataTable.js
    |   |   |       Form.js
    |   |   |       Header.js
    |   |   |       Loading.js
    |   |   |       Modal.js
    |   |   |       Sidebar.js
    |   |   |       Toast.js
    |   |   |       
    |   |   \---pages
    |   |           Adoptions.js
    |   |           AnimalDetail.js
    |   |           Animals.js
    |   |           Billing.js
    |   |           Dashboard.js
    |   |           Inventory.js
    |   |           Login.js
    |   |           Medical.js
    |   |           Profile.js
    |   |           Settings.js
    |   |           Users.js
    |   |           
    |   \---pages
    |       +---admin
    |       |       dashboard.html
    |       |       
    |       +---auth
    |       |       login.html
    |       |       
    |       \---components
    \---public
        \---uploads
```

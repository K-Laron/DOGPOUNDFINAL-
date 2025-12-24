# Catarman Dog Pound Management System

A comprehensive web-based application designed to streamline the operations of the Catarman Dog Pound. This system handles user management, animal records, adoptions, veterinary data, and inventory with a reactive, user-friendly interface.

## 🚀 Features

*   **User Management**: Role-based access control (Admin, Staff, Veterinarian, Adopter) with secure authentication and robust profile management.
*   **Animal Management**: Complete lifecycle tracking from intake to adoption, including image galleries and status updates.
*   **Adoption Portal**: Dedicated interface for adopters to browse animals, submit requests, and track adoption status.
*   **Medical Records**: Detailed veterinary logs for each animal, accessible by veterinarians and staff.
*   **Inventory System**: Track supplies, monitor stock levels, and receive low-stock alerts.
*   **Reactive UI**: Instant updates across the application without page reloads (e.g., profile changes, status updates).
*   **Modern Interface**: Clean, responsive design with dark/light mode support.

## 🛠️ Tech Stack

*   **Frontend**: HTML5, CSS3 (Custom Design System), JavaScript (ES6+, SPA Architecture).
*   **Backend**: PHP 8.x (Custom MVC Framework), RESTful API.
*   **Database**: MySQL.
*   **Environment**: XAMPP (Apache/MySQL/PHP).

## ⚙️ Installation & Setup

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/K-Laron/dogpound.git
    cd dogpound
    ```

2.  **Database Setup**
    *   Create a new MySQL database named `catarman_dog_pound_db`.
    *   Import the schema: `database/schema.sql`.
    *   Import the seed data: `database/seeders.sql`.

3.  **Configuration**
    *   Navigate to `backend/app/config/`.
    *   Ensure database credentials in `Database.php` match your local setup (default: root/empty).

4.  **Running the Application**
    *   Double-click `start.bat` in the root directory.
    *   The application will launch in background mode (hidden windows).
    *   The browser will automatically open at `http://localhost:3000`.

5.  **Stopping the Application**
    *   Double-click `stop.bat` to gracefully shut down the background servers.
    *   **Note**: Closing the browser does NOT stop the servers. You must use `stop.bat`.



## 📂 Project Structure

*   `/backend`: PHP source code, API logic, and models.
*   `/frontend`: Frontend assets, HTML templates, and JS components.
*   `/database`: detailed SQL schema and seed files.

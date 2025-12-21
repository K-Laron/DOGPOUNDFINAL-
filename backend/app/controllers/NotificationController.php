<?php
/**
 * Notification Controller
 * Aggregates system alerts and notifications
 * 
 * @package AnimalShelter
 */

require_once APP_PATH . '/controllers/BaseController.php';

class NotificationController extends BaseController {
    
    /**
     * Get all notifications
     * GET /notifications
     */
    public function index() {
        $notifications = [];
        
        // 1. Inventory Alerts (Low Stock)
        $stmt = $this->db->prepare("
            SELECT Item_Name, Quantity_On_Hand, Reorder_Level 
            FROM Inventory 
            WHERE Quantity_On_Hand <= Reorder_Level
            LIMIT 5
        ");
        $stmt->execute();
        $lowStock = $stmt->fetchAll();
        
        foreach ($lowStock as $item) {
            $notifications[] = [
                'id' => 'inv_low_' . uniqid(),
                'title' => "Low Stock: {$item['Item_Name']}",
                'message' => "Only {$item['Quantity_On_Hand']} remaining (Reorder Level: {$item['Reorder_Level']})",
                'type' => 'warning',
                'link' => '/inventory',
                'time' => date('c')
            ];
        }

        // 2. Inventory Alerts (Expiring Soon - 30 days)
        $stmt = $this->db->prepare("
            SELECT Item_Name, Expiration_Date 
            FROM Inventory 
            WHERE Expiration_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            LIMIT 5
        ");
        $stmt->execute();
        $expiring = $stmt->fetchAll();

        foreach ($expiring as $item) {
            $days = (new DateTime($item['Expiration_Date']))->diff(new DateTime())->days;
            $notifications[] = [
                'id' => 'inv_exp_' . uniqid(),
                'title' => "Expiring Soon: {$item['Item_Name']}",
                'message' => "Expires in {$days} days ({$item['Expiration_Date']})",
                'type' => 'danger',
                'link' => '/inventory?filter=expiring',
                'time' => date('c')
            ];
        }
        
        // 3. Pending Adoption Requests
        $stmt = $this->db->prepare("
            SELECT ar.RequestID, a.Name as AnimalName, u.FirstName, u.LastName, ar.Request_Date
            FROM Adoption_Requests ar
            JOIN Animals a ON ar.AnimalID = a.AnimalID
            JOIN Users u ON ar.Adopter_UserID = u.UserID
            WHERE ar.Status = 'Pending'
            ORDER BY ar.Request_Date ASC
            LIMIT 5
        ");
        $stmt->execute();
        $pendingAdoptions = $stmt->fetchAll();
        
        foreach ($pendingAdoptions as $request) {
            $notifications[] = [
                'id' => 'adopt_' . $request['RequestID'],
                'title' => "New Adoption Request",
                'message' => "{$request['FirstName']} {$request['LastName']} wants to adopt {$request['AnimalName']}",
                'type' => 'info',
                'link' => '/adoptions',
                'time' => $request['Request_Date']
            ];
        }
        
        // 4. Unpaid Invoices (ALL)
        $stmt = $this->db->prepare("
            SELECT InvoiceID, Total_Amount, Created_At
            FROM Invoices
            WHERE Status = 'Unpaid' 
            AND Is_Deleted = FALSE
            LIMIT 5
        ");
        $stmt->execute();
        $overdueInvoices = $stmt->fetchAll();
        
        foreach ($overdueInvoices as $invoice) {
            $notifications[] = [
                'id' => 'inv_due_' . $invoice['InvoiceID'],
                'title' => "Unpaid Invoice #{$invoice['InvoiceID']}",
                'message' => "Amount: ₱" . number_format($invoice['Total_Amount'], 2),
                'type' => 'danger',
                'link' => '/billing',
                'time' => $invoice['Created_At']
            ];
        }

        // 5. Medical Treatments Due (Today or Tomorrow)
        $stmt = $this->db->prepare("
            SELECT a.Name, mr.Diagnosis_Type, mr.Next_Due_Date
            FROM Medical_Records mr
            JOIN Animals a ON mr.AnimalID = a.AnimalID
            WHERE mr.Next_Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            LIMIT 5
        ");
        $stmt->execute();
        $treatments = $stmt->fetchAll();

        foreach ($treatments as $treatment) {
            $notifications[] = [
                'id' => 'med_' . uniqid(),
                'title' => "Treatment Due: {$treatment['Name']}",
                'message' => "{$treatment['Diagnosis_Type']} due on {$treatment['Next_Due_Date']}",
                'type' => 'warning',
                'link' => '/medical',
                'time' => date('c')
            ];
        }
        
        // Sort by time (newest first)
        usort($notifications, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        Response::success($notifications, "Notifications retrieved");
    }
}

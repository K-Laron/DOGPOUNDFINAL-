<?php
/**
 * Feeding Record Model
 * Handles feeding record database operations
 * 
 * @package AnimalShelter
 */

class FeedingRecord {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * @var string Table name
     */
    private $table = 'Feeding_Records';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Find feeding record by ID
     * 
     * @param int $id Feeding ID
     * @return array|false Record data or false
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT fr.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type,
                   u.FirstName, u.LastName
            FROM {$this->table} fr
            JOIN Animals a ON fr.AnimalID = a.AnimalID
            JOIN Users u ON fr.Fed_By_UserID = u.UserID
            WHERE fr.FeedingID = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get feeding records by animal
     * 
     * @param int $animalId Animal ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array ['data' => [], 'total' => int]
     */
    public function getByAnimal($animalId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        // Get total
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM {$this->table} WHERE AnimalID = :animal_id");
        $countStmt->execute(['animal_id' => $animalId]);
        $total = (int)$countStmt->fetch()['total'];
        
        // Get data
        $stmt = $this->db->prepare("
            SELECT fr.*, u.FirstName, u.LastName
            FROM {$this->table} fr
            JOIN Users u ON fr.Fed_By_UserID = u.UserID
            WHERE fr.AnimalID = :animal_id
            ORDER BY fr.Feeding_Time DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':animal_id', $animalId);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }
    
    /**
     * Get today's feeding records
     * 
     * @return array Today's records
     */
    public function getToday() {
        $stmt = $this->db->prepare("
            SELECT fr.*, 
                   a.Name as Animal_Name, a.Type as Animal_Type,
                   u.FirstName, u.LastName
            FROM {$this->table} fr
            JOIN Animals a ON fr.AnimalID = a.AnimalID
            JOIN Users u ON fr.Fed_By_UserID = u.UserID
            WHERE DATE(fr.Feeding_Time) = CURDATE()
            ORDER BY fr.Feeding_Time DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get today's summary
     * 
     * @return array Summary data
     */
    public function getTodaySummary() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT AnimalID) as animals_fed,
                COUNT(*) as total_feedings,
                SUM(Quantity_Used) as total_quantity
            FROM {$this->table}
            WHERE DATE(Feeding_Time) = CURDATE()
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get animals not fed today
     * 
     * @return array Animals not fed
     */
    public function getAnimalsNotFedToday() {
        $stmt = $this->db->prepare("
            SELECT a.AnimalID, a.Name, a.Type, a.Current_Status
            FROM Animals a
            WHERE a.Is_Deleted = FALSE
            AND a.Current_Status NOT IN ('Adopted', 'Deceased', 'Reclaimed')
            AND a.AnimalID NOT IN (
                SELECT DISTINCT AnimalID 
                FROM {$this->table} 
                WHERE DATE(Feeding_Time) = CURDATE()
            )
            ORDER BY a.Name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create feeding record
     * 
     * @param array $data Record data
     * @return int|false Feeding ID or false
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                AnimalID,
                Fed_By_UserID,
                Feeding_Time,
                Food_Type,
                Quantity_Used,
                Created_At
            ) VALUES (
                :animal_id,
                :user_id,
                :feeding_time,
                :food_type,
                :quantity,
                NOW()
            )
        ");
        
        $result = $stmt->execute([
            'animal_id' => $data['animal_id'],
            'user_id' => $data['user_id'],
            'feeding_time' => $data['feeding_time'] ?? date('Y-m-d H:i:s'),
            'food_type' => $data['food_type'],
            'quantity' => $data['quantity_used']
        ]);
        
        return $result ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Delete feeding record
     * 
     * @param int $id Feeding ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE FeedingID = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Get feeding statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_feedings,
                COUNT(DISTINCT AnimalID) as unique_animals_fed,
                SUM(Quantity_Used) as total_quantity,
                AVG(Quantity_Used) as avg_quantity,
                COUNT(DISTINCT DATE(Feeding_Time)) as feeding_days
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get food consumption by type
     * 
     * @param int $days Number of days
     * @return array Consumption data
     */
    public function getConsumptionByType($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                Food_Type,
                SUM(Quantity_Used) as total_quantity,
                COUNT(*) as feeding_count
            FROM {$this->table}
            WHERE Feeding_Time >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY Food_Type
            ORDER BY total_quantity DESC
        ");
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get daily consumption for date range
     * 
     * @param int $days Number of days
     * @return array Daily consumption
     */
    public function getDailyConsumption($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(Feeding_Time) as date,
                COUNT(*) as feedings,
                COUNT(DISTINCT AnimalID) as animals_fed,
                SUM(Quantity_Used) as total_quantity
            FROM {$this->table}
            WHERE Feeding_Time >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(Feeding_Time)
            ORDER BY date ASC
        ");
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
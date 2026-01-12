<?php
/**
 * Fee Calculator Utility
 * Automatically calculates adoption and reclaim fees
 * 
 * @package AnimalShelter
 */

class FeeCalculator {
    /**
     * @var PDO Database connection
     */
    private $db;
    
    /**
     * Fee configuration (in PHP currency)
     * These values can be adjusted based on client requirements
     */
    private $config = [
        // Adoption fee components
        'adoption_base_fee' => 500.00,
        'spay_neuter_fee' => 300.00,
        'vaccination_fee' => 200.00,  // Per vaccination
        'treatment_discount' => 100.00,
        
        // Reclaim fee components
        'reclaim_base_fee' => 200.00,
        'daily_rate' => 50.00
    ];
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Calculate adoption fee for an animal
     * 
     * @param int $animalId Animal ID
     * @return array Fee breakdown with total
     */
    public function calculateAdoptionFee($animalId) {
        $breakdown = [];
        $total = 0;
        
        // Get animal info
        $animal = $this->getAnimal($animalId);
        if (!$animal) {
            return ['error' => 'Animal not found', 'total' => 0, 'breakdown' => []];
        }
        
        // Base fee
        $breakdown[] = [
            'description' => 'Base Adoption Fee',
            'amount' => $this->config['adoption_base_fee']
        ];
        $total += $this->config['adoption_base_fee'];
        
        // Check if spayed/neutered (from medical records)
        if ($this->isSpayedNeutered($animalId)) {
            $breakdown[] = [
                'description' => 'Spayed/Neutered',
                'amount' => $this->config['spay_neuter_fee']
            ];
            $total += $this->config['spay_neuter_fee'];
        }
        
        // Count vaccinations
        $vaccinationCount = $this->getVaccinationCount($animalId);
        if ($vaccinationCount > 0) {
            $vaccinationAmount = $vaccinationCount * $this->config['vaccination_fee'];
            $breakdown[] = [
                'description' => "Vaccinations ({$vaccinationCount})",
                'amount' => $vaccinationAmount
            ];
            $total += $vaccinationAmount;
        }
        
        // Discount for animals in treatment
        if ($animal['Current_Status'] === 'In Treatment') {
            $breakdown[] = [
                'description' => 'In Treatment Discount',
                'amount' => -$this->config['treatment_discount']
            ];
            $total -= $this->config['treatment_discount'];
        }
        
        return [
            'animal_id' => $animalId,
            'animal_name' => $animal['Name'],
            'transaction_type' => 'Adoption Fee',
            'breakdown' => $breakdown,
            'total' => max(0, $total) // Ensure non-negative
        ];
    }
    
    /**
     * Calculate reclaim fee for an animal
     * 
     * @param int $animalId Animal ID
     * @return array Fee breakdown with total
     */
    public function calculateReclaimFee($animalId) {
        $breakdown = [];
        $total = 0;
        
        // Get animal info
        $animal = $this->getAnimal($animalId);
        if (!$animal) {
            return ['error' => 'Animal not found', 'total' => 0, 'breakdown' => []];
        }
        
        // Get impound record
        $impoundRecord = $this->getImpoundRecord($animalId);
        
        // Base fee
        $breakdown[] = [
            'description' => 'Base Reclaim Fee',
            'amount' => $this->config['reclaim_base_fee']
        ];
        $total += $this->config['reclaim_base_fee'];
        
        // Calculate days stayed
        if ($impoundRecord && !empty($impoundRecord['Capture_Date'])) {
            $captureDate = new DateTime($impoundRecord['Capture_Date']);
            $today = new DateTime();
            $daysStayed = max(1, $captureDate->diff($today)->days); // Minimum 1 day
            
            $dailyFee = $daysStayed * $this->config['daily_rate'];
            $breakdown[] = [
                'description' => "Daily Fee ({$daysStayed} days × ₱" . number_format($this->config['daily_rate'], 2) . ")",
                'amount' => $dailyFee
            ];
            $total += $dailyFee;
        } else {
            // If no impound record, use intake date
            $intakeDate = new DateTime($animal['Intake_Date']);
            $today = new DateTime();
            $daysStayed = max(1, $intakeDate->diff($today)->days);
            
            $dailyFee = $daysStayed * $this->config['daily_rate'];
            $breakdown[] = [
                'description' => "Daily Fee ({$daysStayed} days × ₱" . number_format($this->config['daily_rate'], 2) . ")",
                'amount' => $dailyFee
            ];
            $total += $dailyFee;
        }
        
        return [
            'animal_id' => $animalId,
            'animal_name' => $animal['Name'],
            'transaction_type' => 'Reclaim Fee',
            'breakdown' => $breakdown,
            'total' => $total
        ];
    }
    
    /**
     * Calculate fee based on transaction type
     * 
     * @param int $animalId Animal ID
     * @param string $transactionType 'Adoption Fee' or 'Reclaim Fee'
     * @return array Fee breakdown with total
     */
    public function calculateFee($animalId, $transactionType) {
        if ($transactionType === 'Adoption Fee') {
            return $this->calculateAdoptionFee($animalId);
        } elseif ($transactionType === 'Reclaim Fee') {
            return $this->calculateReclaimFee($animalId);
        }
        
        return ['error' => 'Invalid transaction type', 'total' => 0, 'breakdown' => []];
    }
    
    /**
     * Get animal by ID
     * 
     * @param int $animalId
     * @return array|false
     */
    private function getAnimal($animalId) {
        $stmt = $this->db->prepare("
            SELECT AnimalID, Name, Type, Current_Status, Intake_Date 
            FROM Animals 
            WHERE AnimalID = :id AND Is_Deleted = FALSE
        ");
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if animal is spayed/neutered
     * 
     * @param int $animalId
     * @return bool
     */
    private function isSpayedNeutered($animalId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Medical_Records 
            WHERE AnimalID = :id AND Diagnosis_Type = 'Spay/Neuter'
        ");
        $stmt->execute(['id' => $animalId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['count'] > 0);
    }
    
    /**
     * Get vaccination count for animal
     * 
     * @param int $animalId
     * @return int
     */
    private function getVaccinationCount($animalId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM Medical_Records 
            WHERE AnimalID = :id AND Diagnosis_Type = 'Vaccination'
        ");
        $stmt->execute(['id' => $animalId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Get impound record for animal
     * 
     * @param int $animalId
     * @return array|false
     */
    private function getImpoundRecord($animalId) {
        $stmt = $this->db->prepare("
            SELECT * FROM Impound_Records WHERE AnimalID = :id
        ");
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get current fee configuration
     * 
     * @return array
     */
    public function getConfig() {
        return $this->config;
    }
}

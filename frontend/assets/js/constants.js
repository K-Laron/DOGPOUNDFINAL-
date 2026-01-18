/**
 * Constants - Application-wide constants and enums
 * Centralizes magic strings to improve maintainability
 * 
 * @package AnimalShelter
 */

const Constants = {
    /**
     * Animal statuses
     */
    ANIMAL_STATUS: {
        AVAILABLE: 'Available',
        RESERVED: 'Reserved',
        ADOPTED: 'Adopted',
        DECEASED: 'Deceased',
        IN_TREATMENT: 'In Treatment',
        QUARANTINE: 'Quarantine',
        RECLAIMED: 'Reclaimed'
    },

    /**
     * Animal types
     */
    ANIMAL_TYPE: {
        DOG: 'Dog',
        CAT: 'Cat',
        OTHER: 'Other'
    },

    /**
     * Animal genders
     */
    ANIMAL_GENDER: {
        MALE: 'Male',
        FEMALE: 'Female',
        UNKNOWN: 'Unknown'
    },

    /**
     * Intake statuses
     */
    INTAKE_STATUS: {
        STRAY: 'Stray',
        SURRENDERED: 'Surrendered',
        CONFISCATED: 'Confiscated'
    },

    /**
     * Adoption request statuses
     */
    ADOPTION_STATUS: {
        PENDING: 'Pending',
        INTERVIEW_SCHEDULED: 'Interview Scheduled',
        SEMINAR_SCHEDULED: 'Seminar Scheduled',
        APPROVED: 'Approved',
        REJECTED: 'Rejected',
        COMPLETED: 'Completed',
        CANCELLED: 'Cancelled'
    },

    /**
     * Invoice statuses
     */
    INVOICE_STATUS: {
        UNPAID: 'Unpaid',
        PAID: 'Paid',
        CANCELLED: 'Cancelled'
    },

    /**
     * Transaction types
     */
    TRANSACTION_TYPE: {
        ADOPTION_FEE: 'Adoption Fee',
        RECLAIM_FEE: 'Reclaim Fee'
    },

    /**
     * Payment methods
     */
    PAYMENT_METHOD: {
        CASH: 'Cash',
        GCASH: 'GCash',
        BANK_TRANSFER: 'Bank Transfer'
    },

    /**
     * User roles
     */
    ROLE: {
        ADMIN: 'Admin',
        STAFF: 'Staff',
        VETERINARIAN: 'Veterinarian',
        ADOPTER: 'Adopter'
    },

    /**
     * Account statuses
     */
    ACCOUNT_STATUS: {
        ACTIVE: 'Active',
        INACTIVE: 'Inactive',
        BANNED: 'Banned'
    },

    /**
     * Inventory categories
     */
    INVENTORY_CATEGORY: {
        MEDICAL: 'Medical',
        FOOD: 'Food',
        CLEANING: 'Cleaning',
        SUPPLIES: 'Supplies'
    },

    /**
     * Medical diagnosis types
     */
    DIAGNOSIS_TYPE: {
        CHECKUP: 'Checkup',
        VACCINATION: 'Vaccination',
        SURGERY: 'Surgery',
        TREATMENT: 'Treatment',
        EMERGENCY: 'Emergency',
        DEWORMING: 'Deworming',
        SPAY_NEUTER: 'Spay/Neuter',
        EUTHANASIA: 'Euthanasia'
    },

    /**
     * Status options for dropdowns
     */
    getAnimalStatusOptions() {
        return [
            { value: this.ANIMAL_STATUS.AVAILABLE, label: 'Available' },
            { value: this.ANIMAL_STATUS.RESERVED, label: 'Reserved' },
            { value: this.ANIMAL_STATUS.IN_TREATMENT, label: 'In Treatment' },
            { value: this.ANIMAL_STATUS.QUARANTINE, label: 'Quarantine' },
            { value: this.ANIMAL_STATUS.ADOPTED, label: 'Adopted' },
            { value: this.ANIMAL_STATUS.RECLAIMED, label: 'Reclaimed' },
            { value: this.ANIMAL_STATUS.DECEASED, label: 'Deceased' }
        ];
    },

    /**
     * Get editable animal statuses (can be changed by staff)
     */
    getEditableAnimalStatuses() {
        return [
            this.ANIMAL_STATUS.AVAILABLE,
            this.ANIMAL_STATUS.RESERVED,
            this.ANIMAL_STATUS.IN_TREATMENT,
            this.ANIMAL_STATUS.QUARANTINE,
            this.ANIMAL_STATUS.DECEASED
        ];
    },

    /**
     * Non-editable statuses (animal has left the shelter)
     */
    NON_EDITABLE_STATUSES: ['Adopted', 'Reclaimed'],

    /**
     * Adoption statuses that can be cancelled
     */
    CANCELLABLE_ADOPTION_STATUSES: ['Pending', 'Interview Scheduled', 'Seminar Scheduled'],

    /**
     * Get all adoption statuses for dropdowns
     */
    getAdoptionStatusOptions() {
        return [
            { value: this.ADOPTION_STATUS.PENDING, label: 'Pending' },
            { value: this.ADOPTION_STATUS.INTERVIEW_SCHEDULED, label: 'Interview Scheduled' },
            { value: this.ADOPTION_STATUS.SEMINAR_SCHEDULED, label: 'Seminar Scheduled' },
            { value: this.ADOPTION_STATUS.APPROVED, label: 'Approved' },
            { value: this.ADOPTION_STATUS.REJECTED, label: 'Rejected' },
            { value: this.ADOPTION_STATUS.COMPLETED, label: 'Completed' },
            { value: this.ADOPTION_STATUS.CANCELLED, label: 'Cancelled' }
        ];
    },

    /**
     * Adoption status workflow transitions
     */
    ADOPTION_TRANSITIONS: {
        'Pending': ['Interview Scheduled', 'Approved', 'Rejected'],
        'Interview Scheduled': ['Seminar Scheduled', 'Approved', 'Rejected'],
        'Seminar Scheduled': ['Approved', 'Rejected'],
        'Approved': ['Completed', 'Cancelled'],
        'Rejected': [],
        'Completed': [],
        'Cancelled': []
    }
};

// Make Constants globally available
window.Constants = Constants;

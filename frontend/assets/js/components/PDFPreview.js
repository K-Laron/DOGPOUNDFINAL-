/**
 * PDF Preview Component
 * Shows PDF preview in a modal with Print and Download options
 * 
 * @package AnimalShelter
 */

const PDFPreview = {
    /**
     * Current PDF data
     */
    currentPDF: null,
    currentFilename: null,

    /**
     * Show PDF preview modal
     * @param {jsPDF} doc - The jsPDF document
     * @param {string} filename - The filename for download
     */
    show(doc, filename) {
        if (!doc) {
            Toast.error('No PDF document to preview');
            return;
        }

        this.currentPDF = doc;
        this.currentFilename = filename;

        // Create blob URL for preview
        const pdfBlob = doc.output('blob');
        const pdfUrl = URL.createObjectURL(pdfBlob);

        // Create modal with iframe preview
        const modalContent = `
            <div class="pdf-preview-container">
                <iframe 
                    src="${pdfUrl}" 
                    class="pdf-preview-iframe"
                    title="PDF Preview"
                ></iframe>
            </div>
        `;

        Modal.open({
            title: 'PDF Preview',
            content: modalContent,
            size: 'xl',
            footer: `
                <div class="flex items-center justify-between w-full">
                    <button class="btn btn-secondary" data-action="cancel">
                        Close
                    </button>
                    <div class="flex gap-3">
                        <button class="btn btn-secondary" onclick="PDFPreview.print()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Print
                        </button>
                        <button class="btn btn-primary" onclick="PDFPreview.download()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download
                        </button>
                    </div>
                </div>
            `,
            onClose: () => {
                // Revoke the blob URL when modal closes
                URL.revokeObjectURL(pdfUrl);
                this.currentPDF = null;
                this.currentFilename = null;
            }
        });
    },

    /**
     * Print the current PDF
     */
    print() {
        if (!this.currentPDF) {
            Toast.error('No PDF to print');
            return;
        }

        // Create a new window for printing
        const pdfBlob = this.currentPDF.output('blob');
        const pdfUrl = URL.createObjectURL(pdfBlob);

        const printWindow = window.open(pdfUrl, '_blank');
        if (printWindow) {
            printWindow.addEventListener('load', () => {
                printWindow.print();
            });
        } else {
            Toast.error('Please allow pop-ups to print');
        }
    },

    /**
     * Download the current PDF
     */
    download() {
        if (!this.currentPDF) {
            Toast.error('No PDF to download');
            return;
        }

        this.currentPDF.save(this.currentFilename);
        Toast.success('PDF downloaded successfully');
        Modal.closeAll();
    }
};

// Add PDF preview styles
const pdfPreviewStyles = document.createElement('style');
pdfPreviewStyles.textContent = `
    .pdf-preview-container {
        width: 100%;
        height: 70vh;
        min-height: 500px;
        background: var(--color-gray-100);
        border-radius: var(--radius-md);
        overflow: hidden;
    }
    
    .pdf-preview-iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    
    .modal-size-xl {
        max-width: 900px !important;
    }
`;
document.head.appendChild(pdfPreviewStyles);

// Make PDFPreview globally available
window.PDFPreview = PDFPreview;

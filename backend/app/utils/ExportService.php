<?php

/**
 * Export Service Utility
 * Handles data export in various formats (CSV, JSON, Excel-compatible)
 * 
 * @package AnimalShelter
 */

class ExportService
{
    /**
     * Export data to CSV format
     * 
     * @param array $data Array of associative arrays to export
     * @param string $filename Filename without extension
     * @param array $headers Optional custom headers (keys => labels)
     * @return void Outputs CSV and exits
     */
    public static function toCSV(array $data, string $filename = 'export', array $headers = []): void
    {
        if (empty($data)) {
            self::sendEmptyResponse('csv', $filename);
            return;
        }

        // Get headers from first row if not provided
        if (empty($headers)) {
            $headers = array_keys($data[0]);
            $headerLabels = array_map([self::class, 'formatHeaderLabel'], $headers);
        } else {
            $headerLabels = array_values($headers);
            $headers = array_keys($headers);
        }

        // Set headers for CSV download
        self::setDownloadHeaders('csv', $filename);

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write header row
        fputcsv($output, $headerLabels);

        // Write data rows
        foreach ($data as $row) {
            $rowData = [];
            foreach ($headers as $key) {
                $value = $row[$key] ?? '';
                // Handle nested arrays/objects
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                // Handle boolean values
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                }
                $rowData[] = $value;
            }
            fputcsv($output, $rowData);
        }

        fclose($output);
        self::terminate();
    }

    /**
     * Export data to JSON format
     * 
     * @param array $data Data to export
     * @param string $filename Filename without extension
     * @param bool $pretty Whether to pretty-print JSON
     * @return void Outputs JSON and exits
     */
    public static function toJSON(array $data, string $filename = 'export', bool $pretty = true): void
    {
        self::setDownloadHeaders('json', $filename);

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $exportData = [
            'exported_at' => date('c'),
            'total_records' => count($data),
            'data' => $data
        ];

        echo json_encode($exportData, $flags);
        self::terminate();
    }

    /**
     * Export data to Excel-compatible XML (SpreadsheetML)
     * 
     * @param array $data Array of associative arrays to export
     * @param string $filename Filename without extension
     * @param array $headers Optional custom headers (keys => labels)
     * @param string $sheetName Name of the worksheet
     * @return void Outputs XML and exits
     */
    public static function toExcel(array $data, string $filename = 'export', array $headers = [], string $sheetName = 'Sheet1'): void
    {
        if (empty($data)) {
            self::sendEmptyResponse('xml', $filename);
            return;
        }

        // Get headers from first row if not provided
        if (empty($headers)) {
            $headerKeys = array_keys($data[0]);
            $headerLabels = array_map([self::class, 'formatHeaderLabel'], $headerKeys);
        } else {
            $headerLabels = array_values($headers);
            $headerKeys = array_keys($headers);
        }

        self::setDownloadHeaders('excel', $filename);

        // Build SpreadsheetML XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // Styles
        $xml .= '<Styles>' . "\n";
        $xml .= '  <Style ss:ID="Header">' . "\n";
        $xml .= '    <Font ss:Bold="1"/>' . "\n";
        $xml .= '    <Interior ss:Color="#CCCCCC" ss:Pattern="Solid"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '  <Style ss:ID="Date">' . "\n";
        $xml .= '    <NumberFormat ss:Format="yyyy-mm-dd"/>' . "\n";
        $xml .= '  </Style>' . "\n";
        $xml .= '</Styles>' . "\n";

        // Worksheet
        $xml .= '<Worksheet ss:Name="' . htmlspecialchars($sheetName) . '">' . "\n";
        $xml .= '<Table>' . "\n";

        // Header row
        $xml .= '  <Row>' . "\n";
        foreach ($headerLabels as $label) {
            $xml .= '    <Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($label) . '</Data></Cell>' . "\n";
        }
        $xml .= '  </Row>' . "\n";

        // Data rows
        foreach ($data as $row) {
            $xml .= '  <Row>' . "\n";
            foreach ($headerKeys as $key) {
                $value = $row[$key] ?? '';
                $type = self::getExcelDataType($value);
                
                // Handle nested arrays/objects
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                    $type = 'String';
                }
                // Handle boolean values
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                    $type = 'String';
                }
                
                $xml .= '    <Cell><Data ss:Type="' . $type . '">' . htmlspecialchars((string)$value) . '</Data></Cell>' . "\n";
            }
            $xml .= '  </Row>' . "\n";
        }

        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';

        echo $xml;
        self::terminate();
    }

    /**
     * Send data as inline JSON (for preview, not download)
     * 
     * @param array $data Data to send
     * @return void
     */
    public static function preview(array $data): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $exportData = [
            'preview' => true,
            'total_records' => count($data),
            'sample_records' => array_slice($data, 0, 10),
            'available_formats' => ['csv', 'json', 'excel']
        ];

        echo json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        self::terminate();
    }

    /**
     * Export based on format parameter
     * 
     * @param array $data Data to export
     * @param string $format Export format (csv, json, excel)
     * @param string $filename Filename without extension
     * @param array $headers Optional custom headers
     * @return void
     */
    public static function export(array $data, string $format, string $filename = 'export', array $headers = []): void
    {
        switch (strtolower($format)) {
            case 'csv':
                self::toCSV($data, $filename, $headers);
                break;
            case 'json':
                self::toJSON($data, $filename);
                break;
            case 'excel':
            case 'xlsx':
            case 'xls':
                self::toExcel($data, $filename, $headers);
                break;
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid export format. Supported formats: csv, json, excel'
                ]);
                self::terminate();
        }
    }

    /**
     * Format header key to human-readable label
     * 
     * @param string $key Column key
     * @return string Formatted label
     */
    private static function formatHeaderLabel(string $key): string
    {
        // Replace underscores and camelCase with spaces
        $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $key);
        $label = str_replace('_', ' ', $label);
        return ucwords(strtolower($label));
    }

    /**
     * Determine Excel data type for a value
     * 
     * @param mixed $value Value to check
     * @return string Excel data type
     */
    private static function getExcelDataType($value): string
    {
        if (is_numeric($value) && !is_string($value)) {
            return 'Number';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$value)) {
            return 'String'; // Dates as strings for compatibility
        }
        return 'String';
    }

    /**
     * Set appropriate download headers
     * 
     * @param string $format File format
     * @param string $filename Filename without extension
     */
    private static function setDownloadHeaders(string $format, string $filename): void
    {
        $timestamp = date('Y-m-d_His');
        
        // Clear any previously set headers that might conflict
        if (!headers_sent()) {
            header_remove('Content-Type');
            header_remove('Cache-Control');
            header_remove('Pragma');
        }
        
        switch ($format) {
            case 'csv':
                header('Content-Type: text/csv; charset=UTF-8');
                header("Content-Disposition: attachment; filename=\"{$filename}_{$timestamp}.csv\"");
                break;
            case 'json':
                header('Content-Type: application/json; charset=UTF-8');
                header("Content-Disposition: attachment; filename=\"{$filename}_{$timestamp}.json\"");
                break;
            case 'excel':
                header('Content-Type: application/vnd.ms-excel');
                header("Content-Disposition: attachment; filename=\"{$filename}_{$timestamp}.xls\"");
                break;
        }

        header('Cache-Control: max-age=0');
        header('Pragma: public');
    }

    /**
     * Send empty response for empty data
     * 
     * @param string $format Export format
     * @param string $filename Filename
     */
    private static function sendEmptyResponse(string $format, string $filename): void
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => 'No data to export',
            'total_records' => 0
        ]);
        self::terminate();
    }

    /**
     * Terminate script execution (testable)
     */
    private static function terminate(): void
    {
        if (!defined('PHPUNIT_RUNNING')) {
            exit;
        }
        throw new \RuntimeException("RESPONSE_EXIT");
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Xml3176ErrorMultiSheetExport implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;
    protected $xml_filter_status;
    protected $date_type;
    protected $xml3176_xml_error_catalog_id;
    protected $payment_date_filter;
    protected $imported_by;
    protected $xml_submit_status;
    
    public function __construct($fromDate, $toDate, $xml_filter_status, $date_type, $xml3176_xml_error_catalog_id, $payment_date_filter, $imported_by, $xml_submit_status)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->xml_filter_status = $xml_filter_status;
        $this->date_type = $date_type;
        $this->xml3176_xml_error_catalog_id = $xml3176_xml_error_catalog_id;
        $this->payment_date_filter = $payment_date_filter;
        $this->imported_by = $imported_by;
        $this->xml_submit_status = $xml_submit_status;
    }

    public function sheets(): array
    {
        return [
            new Xml3176ErrorExport($this->fromDate, $this->toDate, $this->xml_filter_status, $this->date_type, $this->xml3176_xml_error_catalog_id, $this->payment_date_filter, $this->imported_by, $this->xml_submit_status),
            new HeinCardErrorExport($this->fromDate, $this->toDate),
        ];
    }
}
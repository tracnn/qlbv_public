<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Qd130ErrorMultiSheetExport implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;
    protected $xml_filter_status;
    protected $date_type;
    protected $qd130_xml_error_catalog_id;
    protected $payment_date_filter;
    protected $imported_by;

    public function __construct($fromDate, $toDate, $xml_filter_status, $date_type, $qd130_xml_error_catalog_id, $payment_date_filter, $imported_by)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->xml_filter_status = $xml_filter_status;
        $this->date_type = $date_type;
        $this->qd130_xml_error_catalog_id = $qd130_xml_error_catalog_id;
        $this->payment_date_filter = $payment_date_filter;
        $this->imported_by = $imported_by;
    }

    public function sheets(): array
    {
        return [
            new Qd130ErrorExport($this->fromDate, $this->toDate, $this->xml_filter_status, $this->date_type, $this->qd130_xml_error_catalog_id, $this->payment_date_filter, $this->imported_by),
            new HeinCardErrorExport($this->fromDate, $this->toDate),
        ];
    }
}
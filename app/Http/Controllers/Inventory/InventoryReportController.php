<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\Inventory\Reports\InventoryReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\Response;
use TCPDF;

class InventoryReportController extends Controller
{
    public function show(Request $request, string $key, InventoryReports $reports): View|Response
    {
        Gate::authorize('reports.view');
        abort_unless(in_array($key, InventoryReports::KEYS, true), 404);

        $report = $reports->build($key, $request->only(['from', 'to', 'section', 'location_id']));

        return match ($request->query('export')) {
            'csv' => $this->csv($report, $key),
            'pdf' => $this->pdf($report, $key),
            default => view('inventory.report', [
                'key' => $key, 'report' => $report, 'locations' => Location::orderBy('name')->get(['id', 'name']),
            ]),
        };
    }

    /** @param array{title: string, headers: list<string>, rows: list<list<mixed>>} $report */
    private function csv(array $report, string $key): Response
    {
        $csv = Writer::createFromString();
        $csv->setOutputBOM(Writer::BOM_UTF8); // để Excel đọc đúng tiếng Việt
        $csv->insertOne($report['headers']);
        $csv->insertAll($report['rows']);

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventory-'.$key.'-'.now()->format('Ymd').'.csv"',
        ]);
    }

    /** @param array{title: string, headers: list<string>, rows: list<list<mixed>>} $report */
    private function pdf(array $report, string $key): Response
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('dejavusans', '', 7);
        $pdf->AddPage();

        $html = '<h3>'.e($report['title']).'</h3><table border="1" cellpadding="2"><thead><tr>';
        foreach ($report['headers'] as $h) {
            $html .= '<th style="background-color:#eeeeee"><b>'.e($h).'</b></th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($report['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>'.e((string) $cell).'</td>';
            }
            $html .= '</tr>';
        }
        $pdf->writeHTML($html.'</tbody></table>', true, false, true, false, '');

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inventory-'.$key.'-'.now()->format('Ymd').'.pdf"',
        ]);
    }
}

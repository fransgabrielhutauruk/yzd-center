<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class XlsxExportService
{
    /**
     * Konversi index kolom numerik (0-based) ke format huruf Excel (A, B, ..., Z, AA, AB, dst.).
     */
    public static function getColumnLetter(int $index): string
    {
        $letter = '';
        $index++;

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    /**
     * Menghasilkan dan mengunduh berkas .xlsx dengan kustomisasi kolom mendalam.
     *
     * @param  array<int, array{
     *     key?: string,
     *     label: string,
     *     width?: int,
     *     align?: 'left'|'center'|'right',
     *     type?: 'text'|'number'|'monospace'
     * }>  $columns
     * @param iterable<mixed>|callable(callable(array<int, mixed>): void): void $rows
     */
    public function download(
        string $filenamePrefix,
        string $sheetTitle,
        array $columns,
        iterable|callable $rows
    ): BinaryFileResponse {
        $filename = Str::slug($filenamePrefix, '_') . '_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'yzd_xlsx_');

        if (!$tempFile) {
            throw new RuntimeException('Gagal membuat berkas sementara untuk ekspor XLSX.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal menginisialisasi berkas arsip XLSX.');
        }

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>');

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>');

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>');

        // 4. xl/workbook.xml
        $cleanSheetTitle = htmlspecialchars(Str::limit(preg_replace('/[\\\\\/?*\[\]]/', '', $sheetTitle) ?: 'Sheet1', 31), ENT_XML1, 'UTF-8');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets>' .
            "<sheet name=\"{$cleanSheetTitle}\" sheetId=\"1\" r:id=\"rId1\"/>" .
            '</sheets>' .
            '</workbook>');

        // 5. xl/styles.xml
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="3">' .
            '<font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>' .
            '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' .
            '<font><sz val="9"/><color rgb="FF334155"/><name val="Consolas"/></font>' .
            '</fonts>' .
            '<fills count="4">' .
            '<fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>' . // Header dark slate #0F172A
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>' . // Zebra slate-50 #F8FAFC
            '</fills>' .
            '<borders count="2">' .
            '<border><left/><right/><top/><bottom/></border>' .
            '<border>' .
            '<left style="thin"><color rgb="FFE2E8F0"/></left>' .
            '<right style="thin"><color rgb="FFE2E8F0"/></right>' .
            '<top style="thin"><color rgb="FFE2E8F0"/></top>' .
            '<bottom style="thin"><color rgb="FFE2E8F0"/></bottom>' .
            '</border>' .
            '</borders>' .
            '<cellStyleXfs count="1">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' .
            '</cellStyleXfs>' .
            '<cellXfs count="10">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . // 0: Default
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>' . // 1: Header
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>' . // 2: Regular Left
            '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>' . // 3: Zebra Left
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="top"/></xf>' . // 4: Regular Center
            '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="top"/></xf>' . // 5: Zebra Center
            '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>' . // 6: Regular Monospace
            '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>' . // 7: Zebra Monospace
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="right" vertical="top"/></xf>' . // 8: Regular Right
            '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="right" vertical="top"/></xf>' . // 9: Zebra Right
            '</cellXfs>' .
            '</styleSheet>');

        // 6. Definisi Lebar Kolom
        $colsXml = '<cols>';
        foreach ($columns as $idx => $colDef) {
            $colNum = $idx + 1;
            $width = $colDef['width'] ?? max(12, mb_strlen($colDef['label'] ?? '') + 4);
            $colsXml .= "<col min=\"{$colNum}\" max=\"{$colNum}\" width=\"{$width}\" customWidth=\"1\"/>";
        }
        $colsXml .= '</cols>';

        // 7. Header Row
        $headerRowXml = '<row r="1" ht="28" customHeight="1">';
        foreach ($columns as $idx => $colDef) {
            $colLetter = self::getColumnLetter($idx);
            $escapedLabel = htmlspecialchars((string) ($colDef['label'] ?? ''), ENT_XML1, 'UTF-8');
            $headerRowXml .= "<c r=\"{$colLetter}1\" t=\"inlineStr\" s=\"1\"><is><t>{$escapedLabel}</t></is></c>";
        }
        $headerRowXml .= '</row>';

        // 8. Data Rows
        $rowsXml = '';
        $rowIndex = 2;

        $writeRowCallback = function (array $rowValues) use (&$rowsXml, &$rowIndex, $columns): void {
            $isZebra = ($rowIndex % 2 === 0);
            $rowXml = "<row r=\"{$rowIndex}\">";

            foreach ($columns as $cIdx => $colDef) {
                $colLetter = XlsxExportService::getColumnLetter($cIdx);
                $cRef = "{$colLetter}{$rowIndex}";

                // Ambil nilai sel
                $rawVal = array_key_exists($cIdx, $rowValues)
                    ? $rowValues[$cIdx]
                    : ($colDef['key'] && array_key_exists($colDef['key'], $rowValues) ? $rowValues[$colDef['key']] : null);

                // Tentukan style sel berdasarkan perataan & tipe
                $align = $colDef['align'] ?? 'left';
                $type = $colDef['type'] ?? 'text';

                if ($type === 'monospace') {
                    $cStyle = $isZebra ? 7 : 6;
                } elseif ($align === 'center') {
                    $cStyle = $isZebra ? 5 : 4;
                } elseif ($align === 'right' || $type === 'number') {
                    $cStyle = $isZebra ? 9 : 8;
                } else {
                    $cStyle = $isZebra ? 3 : 2;
                }

                if ($rawVal === null || $rawVal === '') {
                    $rowXml .= "<c r=\"{$cRef}\" s=\"{$cStyle}\"/>";
                } elseif ($type === 'number' && is_numeric($rawVal)) {
                    $numVal = (string) $rawVal;
                    $rowXml .= "<c r=\"{$cRef}\" s=\"{$cStyle}\"><v>{$numVal}</v></c>";
                } else {
                    $escaped = htmlspecialchars((string) $rawVal, ENT_XML1, 'UTF-8');
                    $rowXml .= "<c r=\"{$cRef}\" t=\"inlineStr\" s=\"{$cStyle}\"><is><t xml:space=\"preserve\">{$escaped}</t></is></c>";
                }
            }

            $rowXml .= '</row>';
            $rowsXml .= $rowXml;
            $rowIndex++;
        };

        if (is_callable($rows)) {
            $rows($writeRowCallback);
        } else {
            foreach ($rows as $item) {
                if (is_array($item)) {
                    $writeRowCallback($item);
                } elseif (is_object($item)) {
                    $rowArray = [];
                    foreach ($columns as $cIdx => $colDef) {
                        $key = $colDef['key'] ?? null;
                        $rowArray[$cIdx] = $key ? data_get($item, $key) : null;
                    }
                    $writeRowCallback($rowArray);
                }
            }
        }

        $lastRow = max(1, $rowIndex - 1);
        $lastColLetter = self::getColumnLetter(count($columns) - 1);
        $autoFilterRef = "A1:{$lastColLetter}{$lastRow}";

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetViews><sheetView tabSelected="1" workbookViewId="0">' .
            '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>' .
            '</sheetView></sheetViews>' .
            $colsXml .
            '<sheetData>' .
            $headerRowXml .
            $rowsXml .
            '</sheetData>' .
            "<autoFilter ref=\"{$autoFilterRef}\"/>" .
            '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Memetakan data secara otomatis berdasarkan definisi kolom AppDataTable.
     * Mengabaikan kolom aksi ('actions') dan kolom tanpa label.
     *
     * @param iterable<mixed> $items
     * @param array<int, array{key?: string, label?: string, align?: string}> $tableColumns
     */
    public function downloadFromTableColumns(
        string $filenamePrefix,
        string $sheetTitle,
        iterable $items,
        array $tableColumns
    ): BinaryFileResponse {
        $columns = [];

        foreach ($tableColumns as $col) {
            $key = $col['key'] ?? '';
            $label = $col['label'] ?? '';

            if (empty($label) || $key === 'actions' || $key === 'action') {
                continue;
            }

            $align = $col['align'] ?? 'left';
            $columns[] = [
                'key'   => $key,
                'label' => $label,
                'align' => in_array($align, ['left', 'center', 'right'], true) ? $align : 'left',
            ];
        }

        return $this->download($filenamePrefix, $sheetTitle, $columns, $items);
    }
}

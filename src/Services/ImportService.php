<?php

namespace KCM\Services;

use KCM\Models\Client;

if (!defined('ABSPATH')) {
    exit;
}

class ImportService
{
    public static function importSpreadsheet(string $tmpPath, string $originalFilename, bool $createWpUsers = false): array
    {
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

        try {
            if ($ext === 'csv') {
                $rows = self::parseCsv($tmpPath);
            } elseif ($ext === 'xlsx') {
                $rows = self::parseXlsx($tmpPath);
            } elseif ($ext === 'xls') {
                return [
                    'success' => false,
                    'message' => 'O formato .xls antigo não é suportado. Por favor, converta ou salve o arquivo como .xlsx ou .csv.',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Formato de arquivo inválido. Apenas arquivos .csv e .xlsx são aceitos.',
                ];
            }
        } catch (\Throwable $e) {
            LogService::error('Erro ao ler arquivo de importação', ['exception' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Erro ao processar o arquivo: ' . $e->getMessage(),
            ];
        }

        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'Nenhum dado válido foi encontrado na planilha enviada.',
            ];
        }

        $imported = 0;
        $errors = 0;

        foreach ($rows as $index => $row) {
            $name    = isset($row['name']) ? trim((string) $row['name']) : '';
            $email   = isset($row['email']) ? trim((string) $row['email']) : '';
            $phone   = isset($row['phone']) ? trim((string) $row['phone']) : '';
            $company = isset($row['company']) ? trim((string) $row['company']) : '';
            $status  = isset($row['status']) ? trim((string) $row['status']) : 'lead';

            // Skip row if both name and email are empty
            if (empty($name) && empty($email)) {
                continue;
            }

            $kommoId = 0;
            if (!empty($row['kommo_id']) && is_numeric($row['kommo_id'])) {
                $kommoId = (int) $row['kommo_id'];
            }

            // If no kommo_id provided, check if client already exists by email
            if ($kommoId <= 0 && !empty($email)) {
                $existing = Client::findByEmail($email);
                if ($existing && !empty($existing['kommo_id'])) {
                    $kommoId = (int) $existing['kommo_id'];
                }
            }

            // If still no kommo_id, generate a synthetic unique ID
            if ($kommoId <= 0) {
                $kommoId = Client::generateSyntheticKommoId();
            }

            $payload = [
                'kommo_id' => $kommoId,
                'name'     => $name ?: 'Cliente Importado',
                'email'    => $email,
                'phone'    => $phone,
                'company'  => $company,
                'status'   => $status,
            ];

            try {
                if ($createWpUsers && !empty($email)) {
                    $wpUserId = UserService::createOrMatchUser($payload);
                    if ($wpUserId) {
                        $payload['wp_user_id'] = $wpUserId;
                    }
                }

                $savedId = Client::save($payload);
                if ($savedId > 0) {
                    $imported++;
                } else {
                    $errors++;
                }
            } catch (\Throwable $e) {
                $errors++;
                LogService::error('Erro ao salvar cliente da planilha', [
                    'row'       => $index + 1,
                    'payload'   => $payload,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        LogService::info("Importação de planilha '{$originalFilename}' concluída: {$imported} clientes importados/atualizados, {$errors} erros.");

        return [
            'success'  => true,
            'imported' => $imported,
            'errors'   => $errors,
        ];
    }

    public static function parseCsv(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        // Check UTF-8 BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Detect delimiter from first line
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fseek($handle, 3);
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers = array_map([self::class, 'normalizeHeader'], $headers);

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty(array_filter($data, function ($v) { return trim($v) !== ''; }))) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $headerKey) {
                if (empty($headerKey)) {
                    continue;
                }
                $row[$headerKey] = isset($data[$index]) ? trim($data[$index]) : '';
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    public static function parseXlsx(string $filePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('A extensão ZipArchive do PHP é necessária para importar arquivos .xlsx. Salve a planilha como .csv e tente novamente.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Não foi possível abrir o arquivo XLSX.');
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } else {
                        foreach ($si->xpath('.//t') as $t) {
                            $text .= (string) $t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // 2. Read sheet1.xml (or first worksheet found)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, 'xl/worksheets/sheet') === 0 && substr($name, -4) === '.xml') {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }

        $zip->close();

        if (!$sheetXml) {
            throw new \Exception('Nenhuma aba/planilha válida foi encontrada no arquivo XLSX.');
        }

        $xml = @simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData)) {
            return [];
        }

        $rawRows = [];
        foreach ($xml->sheetData->row as $rowNode) {
            $rowCells = [];
            foreach ($rowNode->c as $cellNode) {
                $cellRef = (string) $cellNode['r'];
                $colLetter = preg_replace('/[0-9]/', '', $cellRef);
                $colIndex = self::colLetterToIndex($colLetter);

                $type = (string) $cellNode['t'];
                $val = isset($cellNode->v) ? (string) $cellNode->v : '';

                if ($type === 's' && isset($sharedStrings[(int) $val])) {
                    $cellValue = $sharedStrings[(int) $val];
                } elseif ($type === 'inlineStr' && isset($cellNode->is->t)) {
                    $cellValue = (string) $cellNode->is->t;
                } else {
                    $cellValue = $val;
                }

                $rowCells[$colIndex] = trim($cellValue);
            }
            if (!empty($rowCells)) {
                ksort($rowCells);
                $rawRows[] = $rowCells;
            }
        }

        if (empty($rawRows)) {
            return [];
        }

        // Header row is the first row
        $headerRow = array_shift($rawRows);
        $headers = [];
        foreach ($headerRow as $idx => $val) {
            $headers[$idx] = self::normalizeHeader((string) $val);
        }

        $rows = [];
        foreach ($rawRows as $rowCells) {
            $row = [];
            $hasData = false;
            foreach ($headers as $idx => $headerKey) {
                if (empty($headerKey)) {
                    continue;
                }
                $val = isset($rowCells[$idx]) ? $rowCells[$idx] : '';
                if ($val !== '') {
                    $hasData = true;
                }
                $row[$headerKey] = $val;
            }
            if ($hasData) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private static function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header), 'UTF-8');
        if (function_exists('remove_accents')) {
            $header = remove_accents($header);
        }
        $header = preg_replace('/[^a-z0-9]/', '_', $header);
        $header = preg_replace('/_+/', '_', $header);
        $header = trim($header, '_');

        if (in_array($header, ['nome', 'name', 'fullname', 'nome_completo', 'cliente', 'contact', 'contato'], true)) {
            return 'name';
        }
        if (in_array($header, ['email', 'e_mail', 'mail', 'correio_eletronico'], true)) {
            return 'email';
        }
        if (in_array($header, ['phone', 'telefone', 'celular', 'tel', 'fone', 'whatsapp', 'mobile'], true)) {
            return 'phone';
        }
        if (in_array($header, ['company', 'empresa', 'organizacao', 'raz_o_social', 'razao_social'], true)) {
            return 'company';
        }
        if (in_array($header, ['status', 'fase', 'etapa', 'situacao'], true)) {
            return 'status';
        }
        if (in_array($header, ['kommo_id', 'id_kommo', 'kommo', 'id'], true)) {
            return 'kommo_id';
        }

        return $header;
    }

    private static function colLetterToIndex(string $col): int
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    public static function outputSampleXlsx(): void
    {
        if (!class_exists('ZipArchive')) {
            wp_die('A extensão ZipArchive do PHP é necessária para gerar a planilha modelo.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'kcm_tpl_') . '.xlsx';
        $zip = new \ZipArchive();
        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Clientes" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

            $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
  <borders count="1"><border><left/><right/><top/><bottom/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>');

            $strings = [
                'Nome', 'E-mail', 'Telefone', 'Empresa', 'Status', 'Kommo ID',
                'João Silva', 'joao.silva@exemplo.com', '11988887777', 'Empresa Exemplo LTDA', 'lead', '1001',
                'Maria Santos', 'maria.santos@exemplo.com', '21977776666', 'Tech Solutions', 'cliente', '1002',
                'Carlos Oliveira', 'carlos.oliveira@exemplo.com', '31966665555', 'Inova Digital', 'lead', ''
            ];

            $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
            $ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
            foreach ($strings as $s) {
                $ssXml .= '<si><t>' . htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . '</t></si>';
            }
            $ssXml .= '</sst>';
            $zip->addFromString('xl/sharedStrings.xml', $ssXml);

            $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
            $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
            $sheetXml .= '<sheetData>';

            for ($r = 0; $r < 4; $r++) {
                $rowNum = $r + 1;
                $sheetXml .= '<row r="' . $rowNum . '">';
                for ($c = 0; $c < 6; $c++) {
                    $colLetter = chr(ord('A') + $c);
                    $strIdx = ($r * 6) + $c;
                    $sheetXml .= '<c r="' . $colLetter . $rowNum . '" t="s"><v>' . $strIdx . '</v></c>';
                }
                $sheetXml .= '</row>';
            }

            $sheetXml .= '</sheetData></worksheet>';
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

            $zip->close();
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="modelo_importacao_clientes.xlsx"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }
}

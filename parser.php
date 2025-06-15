<?php

$options = getopt("", [
    "file:",
    "unique-combinations:",
    "format::"
]);

if (!isset($options['file']) || !isset($options['unique-combinations'])) {
    echo "Please follow this format: php parser.php --file=input.csv --unique-combinations=output.csv [--format=csv|json|xml]\n";
    exit(1);
}

$inputFile = $options['file'];
$outputFile = $options['unique-combinations'];
$format = isset($options['format']) ? strtolower($options['format']) : 'csv';

if (!file_exists($inputFile)) {
    echo "File not found: $inputFile\n";
    exit(1);
}

function validateRequiredFields(array $header, array $requiredFields) {
    $missing = array_diff($requiredFields, $header);
    if (!empty($missing)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing));
    }
}
$requiredFields = [
    'brand_name',
    'model_name',
    'colour_name',
    'gb_spec_name',
    'network_name',
    'grade_name',
    'condition_name'
];


switch ($format) {
    case 'csv':
        $rows = array_map('str_getcsv', file($inputFile));
        $header = array_map('trim', $rows[0]);
        $data = array_slice($rows, 1);
        try {
            validateRequiredFields($header, $requiredFields);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'json':
        $json = json_decode(file_get_contents($inputFile), true);
        if (!is_array($json) || empty($json)) {
            echo "Invalid JSON.\n";
            exit(1);
        }
        $header = array_keys($json[0]);
        $data = [];
        foreach ($json as $item) {
            $data[] = array_map('trim', array_values($item));
        }
        try {
            validateRequiredFields($header, $requiredFields);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'xml':
        $xmlContent = simplexml_load_file($inputFile);
        if (!$xmlContent) {
            echo "Failed to parse XML.\n";
            exit(1);
        }

        $data = [];
        $firstRow = null;

        foreach ($xmlContent->row as $row) {
            $entry = [];
            foreach ($row->children() as $field) {
                $entry[] = trim((string) $field);
            }
            if (!$firstRow) {
                $firstRow = array_keys((array) $row);
            }
            $data[] = $entry;
        }

        if (!$firstRow) {
            echo "Empty XML data.\n";
            exit(1);
        }

        $header = $firstRow;
        try {
            validateRequiredFields($header, $requiredFields);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    default:
        echo "Unsupported format: $format\n";
        exit(1);
}



$fieldMap = array_flip($header);


$groupFields = [
    'brand_name',
    'model_name',
    'colour_name',
    'gb_spec_name',
    'network_name',
    'grade_name',
    'condition_name'
];


$combinations = [];

foreach ($data as $row) {
    $product = [];
    foreach ($header as $i => $key) {
        $product[$key] = trim($row[$i] ?? '');
    }

    $keyParts = [];
    foreach ($groupFields as $field) {
        $keyParts[] = strtolower(trim($product[$field] ?? ''));
    }
    $key = implode('|', $keyParts);

    if (!isset($combinations[$key])) {
        $combinations[$key] = ['fields' => $product, 'count' => 0];
    }
    $combinations[$key]['count']++;
}


switch ($format) {
    case 'csv':
        $f = fopen($outputFile, 'w');
        $outputHeader = array_merge($groupFields, ['count']);
        fputcsv($f, $outputHeader);

        foreach ($combinations as $entry) {
            $rowOut = [];
            foreach ($groupFields as $field) {
                $rowOut[] = $entry['fields'][$field] ?? '';
            }
            $rowOut[] = $entry['count'];
            fputcsv($f, $rowOut);
        }
        fclose($f);
        break;

    case 'json':
        $out = [];
        foreach ($combinations as $entry) {
            $row = [];
            foreach ($groupFields as $field) {
                $row[$field] = $entry['fields'][$field] ?? '';
            }
            $row['count'] = $entry['count'];
            $out[] = $row;
        }
        file_put_contents($outputFile, json_encode($out, JSON_PRETTY_PRINT));
        break;

    case 'xml':
        $xml = new SimpleXMLElement('<rows/>');
        foreach ($combinations as $entry) {
            $row = $xml->addChild('row');
            foreach ($groupFields as $field) {
                $row->addChild($field, htmlspecialchars($entry['fields'][$field] ?? ''));
            }
            $row->addChild('count', $entry['count']);
        }
        $xml->asXML($outputFile);
        break;
}

echo "Grouped results written to $outputFile\n";

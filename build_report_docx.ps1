$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
$root = 'C:\xampp\htdocs\CampusConnect'
$htmlPath = Join-Path $env:TEMP 'CampusConnect_Project_Report_Source.html'
$docxPath = Join-Path $root 'CampusConnect_Project_Report.docx'
$html = Get-Content -LiteralPath $htmlPath -Raw
$bodyMatch = [regex]::Match($html, '(?is)<body>(.*)</body>')
if (-not $bodyMatch.Success) { throw 'Report HTML body was not found.' }
$body = $bodyMatch.Groups[1].Value
$blocks = [regex]::Matches($body, '(?is)<div class="(?:chapter|frontbreak)"></div>|<h1\b[^>]*>.*?</h1>|<h2\b[^>]*>.*?</h2>|<p\b[^>]*>.*?</p>|<table\b[^>]*>.*?</table>|<(ul|ol)\b[^>]*>.*?</\1>')
$script:wmlBody = [System.Text.StringBuilder]::new()

function Escape-XmlText([string]$text) {
    return [System.Security.SecurityElement]::Escape($text)
}

function Get-PlainText([string]$fragment) {
    $fragment = [regex]::Replace($fragment, '(?is)<br\s*/?>', ' ')
    $fragment = [regex]::Replace($fragment, '(?is)<[^>]+>', '')
    return [System.Net.WebUtility]::HtmlDecode($fragment)
}

function New-Run([string]$text, [bool]$bold = $false, [bool]$italic = $false) {
    if ($text.Length -eq 0) { return '' }
    $props = ''
    if ($bold) { $props += '<w:b/>' }
    if ($italic) { $props += '<w:i/>' }
    $escaped = Escape-XmlText $text
    return "<w:r><w:rPr>$props</w:rPr><w:t xml:space=`"preserve`">$escaped</w:t></w:r>"
}

function Get-InlineRuns([string]$fragment) {
    $builder = [System.Text.StringBuilder]::new()
    $matches = [regex]::Matches($fragment, '(?is)<(b|strong|i|em)\b[^>]*>.*?</\1>')
    if ($matches.Count -eq 0) {
        return (New-Run (Get-PlainText $fragment))
    }
    $position = 0
    foreach ($match in $matches) {
        if ($match.Index -gt $position) {
            [void]$builder.Append((New-Run (Get-PlainText $fragment.Substring($position, $match.Index - $position))))
        }
        $isBold = $match.Groups[1].Value -match '^(?i:b|strong)$'
        $inner = [regex]::Replace($match.Value, '(?is)^<(b|strong|i|em)\b[^>]*>|</(b|strong|i|em)>$', '')
        [void]$builder.Append((New-Run (Get-PlainText $inner) $isBold (-not $isBold)))
        $position = $match.Index + $match.Length
    }
    if ($position -lt $fragment.Length) {
        [void]$builder.Append((New-Run (Get-PlainText $fragment.Substring($position))))
    }
    return $builder.ToString()
}

function Add-Paragraph([string]$inner, [string]$style = 'Normal', [string]$alignment = '', [bool]$pageBreak = $false) {
    $pPr = "<w:pStyle w:val=`"$style`"/>"
    if ($alignment) { $pPr += "<w:jc w:val=`"$alignment`"/>" }
    if ($pageBreak) { $pPr += '<w:pageBreakBefore/>' }
    $runs = Get-InlineRuns $inner
    [void]$script:wmlBody.Append("<w:p><w:pPr>$pPr</w:pPr>$runs</w:p>")
}

function Add-Table([string]$tableHtml) {
    $rows = [regex]::Matches($tableHtml, '(?is)<tr\b[^>]*>(.*?)</tr>')
    $parsedRows = @()
    $columnCount = 0
    foreach ($row in $rows) {
        $cells = [regex]::Matches($row.Groups[1].Value, '(?is)<t[dh]\b[^>]*>(.*?)</t[dh]>')
        $cellValues = @()
        foreach ($cell in $cells) { $cellValues += ,$cell.Groups[1].Value }
        if ($cellValues.Count -gt $columnCount) { $columnCount = $cellValues.Count }
        $parsedRows += ,$cellValues
    }
    if ($columnCount -eq 0) { return }
    $cellWidth = [math]::Floor(9360 / $columnCount)
    [void]$script:wmlBody.Append('<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="8797A8"/><w:left w:val="single" w:sz="4" w:color="8797A8"/><w:bottom w:val="single" w:sz="4" w:color="8797A8"/><w:right w:val="single" w:sz="4" w:color="8797A8"/><w:insideH w:val="single" w:sz="4" w:color="8797A8"/><w:insideV w:val="single" w:sz="4" w:color="8797A8"/></w:tblBorders></w:tblPr><w:tblGrid>')
    for ($i = 0; $i -lt $columnCount; $i++) { [void]$script:wmlBody.Append("<w:gridCol w:w=`"$cellWidth`"/>") }
    [void]$script:wmlBody.Append('</w:tblGrid>')
    $rowIndex = 0
    foreach ($row in $parsedRows) {
        [void]$script:wmlBody.Append('<w:tr>')
        for ($col = 0; $col -lt $columnCount; $col++) {
            $value = ''
            if ($col -lt $row.Count) { $value = $row[$col] }
            $shade = ''
            if ($rowIndex -eq 0) { $shade = '<w:shd w:fill="DCE6F1"/>' }
            $runs = Get-InlineRuns $value
            [void]$script:wmlBody.Append("<w:tc><w:tcPr><w:tcW w:w=`"$cellWidth`" w:type=`"dxa`"/>$shade</w:tcPr><w:p><w:pPr><w:spacing w:after=`"40`"/></w:pPr>$runs</w:p></w:tc>")
        }
        [void]$script:wmlBody.Append('</w:tr>')
        $rowIndex++
    }
    [void]$script:wmlBody.Append('</w:tbl>')
}

foreach ($block in $blocks) {
    $value = $block.Value
    if ($value -match '^<div class="(?:chapter|frontbreak)"') {
        [void]$wmlBody.Append('<w:p><w:r><w:br w:type="page"/></w:r></w:p>')
    } elseif ($value -match '^<h1\b') {
        $inner = [regex]::Replace($value, '(?is)^<h1\b[^>]*>|</h1>$', '')
        $text = Get-PlainText $inner
        $break = $text -match '^(CHAPTER|APPENDIX|REFERENCES)'
        Add-Paragraph $inner 'Heading1' 'center' $break
    } elseif ($value -match '^<h2\b') {
        $inner = [regex]::Replace($value, '(?is)^<h2\b[^>]*>|</h2>$', '')
        Add-Paragraph $inner 'Heading2'
    } elseif ($value -match '^<p\b') {
        $inner = [regex]::Replace($value, '(?is)^<p\b[^>]*>|</p>$', '')
        $text = Get-PlainText $inner
        if ($text -eq 'CampusConnect') {
            Add-Paragraph $inner 'CoverBrand' 'center'
        } elseif ($text -eq 'PROJECT REPORT') {
            Add-Paragraph $inner 'CoverTitle' 'center'
        } elseif ($text -eq 'System Analysis and Design') {
            Add-Paragraph $inner 'CoverSubtitle' 'center'
        } elseif ($text -match '^(Campus Services Management Portal|Submitted in partial fulfilment|Prepared from the CampusConnect)') {
            Add-Paragraph $inner 'Subtitle' 'center'
        } elseif ($value -match 'class="small"') {
            Add-Paragraph $inner 'Small' 'center'
        } else {
            Add-Paragraph $inner 'Normal'
        }
    } elseif ($value -match '^<table\b') {
        Add-Table $value
    } elseif ($value -match '^<(ul|ol)\b') {
        $ordered = $value -match '^<ol\b'
        $items = [regex]::Matches($value, '(?is)<li\b[^>]*>(.*?)</li>')
        $number = 1
        foreach ($item in $items) {
            $prefix = if ($ordered) { "$number. " } else { [char]0x2022 + ' ' }
            Add-Paragraph ($prefix + $item.Groups[1].Value) 'Normal'
            $number++
        }
    }
}

$documentXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><w:body>$($wmlBody.ToString())<w:sectPr><w:footerReference w:type="default" r:id="rId2"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1224" w:right="1296" w:bottom="1152" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/></w:sectPr></w:body></w:document>
"@
$stylesXml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman"/><w:sz w:val="22"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="140" w:line="320" w:lineRule="auto"/><w:jc w:val="both"/></w:pPr></w:pPrDefault></w:docDefaults>
<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>
<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="240" w:after="260"/><w:jc w:val="center"/></w:pPr><w:rPr><w:b/><w:color w:val="17365D"/><w:sz w:val="34"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="260" w:after="100"/><w:outlineLvl w:val="1"/></w:pPr><w:rPr><w:b/><w:color w:val="17365D"/><w:sz w:val="26"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:pPr><w:jc w:val="center"/></w:pPr><w:rPr><w:color w:val="5B6F82"/><w:sz w:val="28"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="CoverBrand"><w:name w:val="Cover Brand"/><w:basedOn w:val="Normal"/><w:pPr><w:jc w:val="center"/><w:spacing w:before="1600" w:after="180"/></w:pPr><w:rPr><w:b/><w:color w:val="24527A"/><w:sz w:val="58"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="CoverTitle"><w:name w:val="Cover Title"/><w:basedOn w:val="Normal"/><w:pPr><w:jc w:val="center"/><w:spacing w:before="300" w:after="220"/></w:pPr><w:rPr><w:b/><w:color w:val="17365D"/><w:sz w:val="42"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="CoverSubtitle"><w:name w:val="Cover Subtitle"/><w:basedOn w:val="Normal"/><w:pPr><w:jc w:val="center"/></w:pPr><w:rPr><w:b/><w:color w:val="24527A"/><w:sz w:val="32"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Small"><w:name w:val="Small"/><w:basedOn w:val="Normal"/><w:rPr><w:color w:val="526170"/><w:sz w:val="19"/></w:rPr></w:style>
</w:styles>
'@
$relsXml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/></Relationships>
'@
$rootRelsXml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>
'@
$contentTypesXml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/></Types>
'@
$footerXml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>CampusConnect Project Report | Page </w:t></w:r><w:r><w:fldChar w:fldCharType="begin"/></w:r><w:r><w:instrText xml:space="preserve"> PAGE </w:instrText></w:r><w:r><w:fldChar w:fldCharType="end"/></w:r></w:p></w:ftr>
'@

$stream = [System.IO.File]::Open($docxPath, [System.IO.FileMode]::Create)
try {
    $zip = [System.IO.Compression.ZipArchive]::new($stream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
    try {
        $parts = [ordered]@{
            '[Content_Types].xml' = $contentTypesXml
            '_rels/.rels' = $rootRelsXml
            'word/document.xml' = $documentXml
            'word/styles.xml' = $stylesXml
            'word/_rels/document.xml.rels' = $relsXml
            'word/footer1.xml' = $footerXml
        }
        foreach ($part in $parts.GetEnumerator()) {
            $entry = $zip.CreateEntry($part.Key)
            $writer = [System.IO.StreamWriter]::new($entry.Open(), [System.Text.UTF8Encoding]::new($false))
            try { $writer.Write($part.Value) } finally { $writer.Dispose() }
        }
    } finally { $zip.Dispose() }
} finally { $stream.Dispose() }

$checkStream = [System.IO.File]::OpenRead($docxPath)
$check = [System.IO.Compression.ZipArchive]::new($checkStream, [System.IO.Compression.ZipArchiveMode]::Read, $false)
try {
    if ($check.GetEntry('word/document.xml') -eq $null) { throw 'DOCX document part was not created.' }
    $entry = $check.GetEntry('word/document.xml')
    $reader = [System.IO.StreamReader]::new($entry.Open())
    try { [xml]$null = $reader.ReadToEnd() } finally { $reader.Dispose() }
} finally { $check.Dispose(); $checkStream.Dispose() }
Get-Item $docxPath | Select-Object Name, FullName, Length

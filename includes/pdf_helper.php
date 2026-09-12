<?php
class SimplePDF {
    private $pages = [];
    private $currentPage;
    private $w = 595;
    private $h = 842;
    private $marginT = 40;
    private $marginB = 40;
    private $marginL = 40;
    private $marginR = 40;
    private $fontName = 'Helvetica';
    private $fontStyle = '';
    private $fontSize = 11;
    public $y;
    public $x;
    private $n = 2;
    private $buffer;
    private $widths = [];

    public function __construct() {
        $this->widths = [
            'Helvetica' => [
                ' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,'\''=>191,
                '('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,
                '0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,
                '8'=>556,'9'=>556,':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,
                '@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,
                'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,
                'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,
                'X'=>667,'Y'=>667,'Z'=>611,'['=>278,'\\'=>278,']'=>278,'^'=>469,'_'=>556,
                '`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,
                'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,
                'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,
                'x'=>500,'y'=>500,'z'=>500,'{'=>334,'|'=>260,'}'=>334,'~'=>584
            ]
        ];
    }

    public function AddPage() {
        if ($this->currentPage) $this->pages[] = $this->currentPage;
        $this->currentPage = '';
        $this->y = $this->marginT;
        $this->x = $this->marginL;
        $this->n += 2;
    }

    public function SetFont($family, $style='', $size=0) {
        $this->fontName = $family;
        $this->fontStyle = $style;
        if ($size > 0) $this->fontSize = $size;
    }

    public function GetStringWidth($s) {
        if (!isset($this->widths[$this->fontName])) return strlen($s) * $this->fontSize * 0.5;
        $w = $this->widths[$this->fontName];
        $sum = 0;
        $s = (string)$s;
        for ($i = 0; $i < strlen($s); $i++) {
            $c = $s[$i];
            $sum += $w[$c] ?? 500;
        }
        return $sum * $this->fontSize / 1000;
    }

    public function Ln($h = null) {
        $this->y += $h ?? ($this->fontSize * 1.4);
        $this->x = $this->marginL;
    }

    public function Cell($w, $h, $txt='', $border=0, $ln=0, $align='L', $fill=false) {
        $txt = (string)$txt;
        $x = $this->x;
        $y = $this->y;
        if ($fill || $border) {
            $op = '';
            if ($fill) $op .= 'B';
            if ($border) {
                $this->_drawRect($x, $y, $w, $h, $border);
                if (!$fill) $op = 'S';
                else $op = 'B';
            }
            if ($op) $this->_out("$op");
        }
        if ($txt !== '') {
            $stringWidth = $this->GetStringWidth($txt);
            if ($align === 'R') $tx = $x + $w - $stringWidth - 2;
            elseif ($align === 'C') $tx = $x + ($w - $stringWidth) / 2;
            else $tx = $x + 2;
            $ty = $y + ($h - $this->fontSize) / 2 + $this->fontSize * 0.3;
            $this->_text($tx, $ty, $txt);
        }
        $this->x += $w;
        if ($ln) {
            $this->y += $h;
            $this->x = $this->marginL;
        }
    }

    public function MultiCell($w, $h, $txt, $border=0, $align='L') {
        $txt = (string)$txt;
        $lines = explode("\n", str_replace(["\r\n","\r"], "\n", $txt));
        foreach ($lines as $line) {
            if ($line === '') { $this->Ln($h); continue; }
            $out = '';
            $cw = 0;
            $chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($chars as $ch) {
                $cWidth = $this->GetStringWidth($ch);
                if ($cw + $cWidth > $w - 4 && $out !== '') {
                    $this->Cell($w, $h, $out, $border, 1, $align);
                    $out = $ch;
                    $cw = $cWidth;
                } else {
                    $out .= $ch;
                    $cw += $cWidth;
                }
            }
            if ($out !== '') $this->Cell($w, $h, $out, $border, 1, $align);
        }
    }

    public function Line($x1, $y1, $x2, $y2) {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $this->h - $y1, $x2, $this->h - $y2));
    }

    public function Rect($x, $y, $w, $h, $style='') {
        $this->_drawRect($x, $y, $w, $h, $style === 'S' ? 1 : 0);
        $this->_out($style ?: 'S');
    }

    private function _drawRect($x, $y, $w, $h, $border) {
        $y2 = $this->h - $y;
        $y1 = $y2 - $h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re', $x, $y1, $w, $h));
    }

    private function _text($x, $y, $txt) {
        $s = $this->_escape($txt);
        $this->_out('BT');
        $fontKey = $this->fontName . ($this->fontStyle === 'B' ? '-Bold' : ($this->fontStyle === 'I' ? '-Oblique' : ''));
        $this->_out("/F1 {$this->fontSize} Tf");
        $this->_out(sprintf('%.2F %.2F Td', $x, $this->h - $y));
        $this->_out("($s) Tj");
        $this->_out('ET');
    }

    private function _escape($s) {
        return str_replace(['\\','(',')'], ['\\\\','\\(', '\\)'], $s);
    }

    private function _out($s) {
        $this->currentPage .= $s . "\n";
    }

    public function Output($name = 'doc.pdf', $dest = 'I') {
        if ($this->currentPage) $this->pages[] = $this->currentPage;
        if (empty($this->pages)) $this->AddPage();

        $pages = [];
        foreach ($this->pages as $p) $pages[] = $p;

        $output = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
        $offsets = [];
        $objNum = 1;

        $offsets[$objNum] = strlen($output);
        $output .= "$objNum 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objNum++;

        $kids = '';
        for ($i = 0; $i < count($pages); $i++) {
            $pgObj = 3 + $i * 2;
            $kids .= "$pgObj 0 R ";
        }
        $offsets[$objNum] = strlen($output);
        $output .= "$objNum 0 obj\n<< /Type /Pages /Kids [{$kids}] /Count " . count($pages) . " >>\nendobj\n";
        $objNum++;

        $contentObjs = [];
        foreach ($pages as $idx => $content) {
            $pageObj = $objNum;
            $contentObj = $objNum + 1;
            $contentObjs[] = $contentObj;
            $offsets[$objNum] = strlen($output);
            $output .= "$objNum 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->w} {$this->h}] /Resources << /Font << /F1 9 0 R >> >> /Contents {$contentObj} 0 R >>\nendobj\n";
            $objNum++;
            $offsets[$objNum] = strlen($output);
            $stream = $content;
            $output .= "$objNum 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream\nendobj\n";
            $objNum++;
        }

        $fontObj = $objNum;
        $offsets[$objNum] = strlen($output);
        $output .= "$objNum 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";
        $objNum++;

        $offsetXref = strlen($output);
        $output .= "xref\n0 " . ($objNum) . "\n";
        $output .= "0000000000 65535 f \n";
        for ($i = 1; $i < $objNum; $i++) {
            $off = $offsets[$i] ?? 0;
            $output .= sprintf('%010d 00000 n ', $off) . "\n";
        }
        $output .= "trailer\n<< /Size " . ($objNum) . " /Root 1 0 R >>\nstartxref\n" . $offsetXref . "\n%%EOF";

        if ($dest === 'S') return $output;

        header('Content-Type: application/pdf');
        if ($dest === 'D') {
            header('Content-Disposition: attachment; filename="' . $name . '"');
        } else {
            header('Content-Disposition: inline; filename="' . $name . '"');
        }
        header('Content-Length: ' . strlen($output));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $output;
        exit;
    }
}
?>

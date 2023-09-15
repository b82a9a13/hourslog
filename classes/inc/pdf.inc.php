<?php
if(!isset($hlarray) && !isset($parray) && !isset($iarray)){
    echo("<h1 class='text-center'>Error</h1>");
    exit();
}
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/tcpdf/tcpdf.php');
class MYPDF extends TCPDF{
    public function Header(){
        $this->Image('./../img/ntalogo.png', $this->GetPageWidth() - 32, $this->GetPageHeight() - 22, 30, 20, 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
    }
    public function Footer(){

    }
}

//Create variables
$headersize = 26;
$tabletext = 11;
$font = 'Times';
$coursename = $lib->get_course_fullname($cid);

//Create pdf
$pdf = new MyPDF('L', 'mm', 'A4');

//Set pdf values and create the title
$pdf->AddPage('L');
$pdf->setPrintHeader(true);
$pdf->setFont($font, 'B', $headersize);
$pdf->Cell(0, 0, get_string('otj_ht', $p).' - '.$fullname, 0, 0, 'C', 0, '', 0);
$pdf->Ln();

//Add in hours log table and data
$pdf->setFont($font, '', $tabletext);
$html = '<table border="1" cellpadding="2"><thead><tr>
    <th width="20px" bgcolor="#95287A" style="color: #fafafa;"><b>'.get_string('id', $p).'</b></th>
    <th width="75px" bgcolor="#95287A" style="color: #fafafa;"><b>'.get_string('date', $p).'</b></th>
    <th width="183px" bgcolor="#95287A" style="color: #fafafa;"><b>'.get_string('activity', $p).'</b></th>
    <th width="183px" bgcolor="#95287A" style="color: #fafafa;"><b>'.get_string('what_link_title', $p).'</b></th>
    <th width="183px" bgcolor="#95287A" style="color: #fafafa;"><b>'.get_string('what_learn_title', $p).'</b></th>
    <th width="75px" bgcolor="#95287A" style="color: #fafafa;"><b>'.get_string('duration_title', $p).'</b></th>
    <th width="50px" bgcolor="#95287A" style="color: #fafafa;"><b>'.get_string('initials', $p).'</b></th>
</tr></thead><tbody>';
if($hlarray != []){
    foreach($hlarray as $arr){
        $html .= '<tr><td width="20px">'.$arr[0].'</td><td width="75px">'.$arr[2].'</td><td width="183px">'.$arr[3].'</td><td width="183px">'.$arr[4].'</td><td width="183px">'.$arr[5].'</td><td width="75px">'.$arr[6].'</td><td width="50px">'.$arr[8].'</td></tr>';
    }
}
$html .= "</tbody></table>";
$pdf->writeHTML($html, true, false, false, false, false, '');

//Retrieve progress data
$pdf->Ln();
$percent = $parray[0];
$expected = $parray[1];
$height = 6;
$infowidth = 271 / 100;

//Create green progress bar
$pdf->setFillColor(0, 255, 0);
$percent = ($percent <= 0) ? 0.1 : $percent;
$pdf->Cell($infowidth * $percent, $height, '', 0, 0, '', 1);
$percent = ($percent === 0.1) ? 0 : $percent;

//Create orange expected bar
$pdf->setFillColor(255, 165, 0);
$expect =  $infowidth * ($expected - $percent);
$expect = ($expect <= 0) ? 0 : $expect;
if($expect != 0){
    $pdf->Cell($expect, $height, '', 0, 0, '', 1);
}

//Create red incomplete bar
$pdf->setFillColor(255, 0, 0);
$incomplete = 100 - ($percent + $expected);
if($incomplete != 0){
    $pdf->Cell($infowidth * $incomplete, $height, '', 0, 0, '', 1);
}
$pdf->Ln();
$pdf->Ln();

//Create green progress value
$pdf->setFillColor(0, 255, 0);
$pdf->Cell($height, $height, '', 0, 0, '', 1);
$pdf->Cell($height, $height, get_string('progress', $p).": $percent%", 0, 0, '', 0);
$pdf->Ln();

//Create orange expected value
$pdf->setFillColor(255, 165, 0);
$pdf->Cell($height, $height, '', 0, 0, '', 1);
$pdf->Cell($height, $height, get_string('expected', $p).": $expected%", 0, 0, '', 0);
$pdf->Ln();

//Create red incomplete value
$pdf->setFillColor(255, 0, 0);
$pdf->Cell($height, $height, '', 0, 0, '', 1);
$pdf->Cell($height, $height, get_string('incomplete', $p).": $incomplete%", 0, 0, '', 0);
$pdf->Ln();

//Create info table
$pdf->setFillColor(220, 220, 220);
$pdf->setFont($font, 'B', 18);
$pdf->Cell(270, $height, get_string('info_table', $p), 0, 0, 'C', 0);
$pdf->Ln();
$pdf->setFont($font, '', $tabletext);
$infowidth = 270 * 0.25;
$pdf->Cell($infowidth, $height, get_string('total_notj_title', $p), 1, 0, 'C', 1);
$pdf->Cell($infowidth, $height, $iarray[0], 1, 0, '', 0);
$pdf->Cell($infowidth, $height, get_string('total_nohl_title', $p), 1, 0, 'C', 1);
$pdf->Cell($infowidth, $height, $iarray[1], 1, 0, '', 0);
$pdf->Ln();
$pdf->Cell($infowidth, $height, get_string('otj_hpw', $p), 1, 0, 'C', 1);
$pdf->Cell($infowidth, $height, $iarray[2], 1, 0, '', 0);
$pdf->Cell($infowidth, $height, get_string('months_op', $p), 1, 0, 'C', 1);
$pdf->Cell($infowidth, $height, $iarray[3], 1, 0, '', 0);
$pdf->Ln();
$pdf->Cell($infowidth, $height, get_string('weeks_op', $p), 1, 0, 'C', 1);
$pdf->Cell($infowidth, $height, $iarray[4], 1, 0, '', 0);
$pdf->Cell($infowidth, $height, get_string('annual_lw', $p), 1, 0, 'C', 1);
$pdf->Cell($infowidth, $height, $iarray[5], 1, 0, '', 0);

//Output the pdf
$pdf->Output("OTJH-$coursename-$fullname.pdf");
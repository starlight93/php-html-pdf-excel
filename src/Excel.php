<?php

namespace Starlight93\HtmlPdfExcel;

use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate as coor;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class Excel {

    public $data;
    public $templateArr;
    public $fontSize; 
    public $break;
    public $isMulti = false;
    public $config = [
        'fontSize' => 11,
        'break' => false
    ];
    public $sp;
    public $linesLength = 1;
    public $similiar = '';

    function __construct( array $data, string $template, array $config = []  ){
        $this->templateArr = explode( "\n", $template."\r\n" ); //    exploded by new line in linux
        $this->isMulti = isset( $data[0] ) && is_array( $data[0] );
        $this->data = $this->isMulti? $data : [$data];
        $this->config = array_merge($this->config, $config);
        $this->fontSize = $this->config[ 'fontSize'];
        $this->break = $this->config[ 'break'];
        $this->defineSpreadsheet();
        $this->isDynamic = strpos( $template, "\t." ) !== false;
    }

    protected function getDynamicTemplate( array $currentData ){
        $oldTemplate = $this->templateArr;
        $fixedTemplate = array_map(function($d){
            return explode("\t", $d);
        }, $oldTemplate);
        
        foreach( $oldTemplate as $rowIdx => $row ){
            $cols = explode("\t", $row);
            $foundIdx = null;
            foreach( $cols as $colIdx => $col ){
                $insertedArr=[];
                $insertedDetailArr=[];
                if( mb_substr($col, 0, 1)  == '.' ){                    
                    $key = str_replace(".","", ($keyArr=explode("::", $col)) [0]);
                    if( ($dynamicCols = @$currentData[$key]) ){
                        foreach($dynamicCols as $key=>$value ){
                            $insertedArr[] =  $key.(@$keyArr[1]?"::".$keyArr[1]:'') ;
                            $insertedDetailArr[] =  $value ;
                        }
                    }
                }
                if( $insertedArr ){
                    $total = count($insertedArr);
                    foreach( $fixedTemplate as $fixIdx=>$fixRow){
                        $fakeValueArr = [];
                        if($rowIdx==$fixIdx){
                            $fakeValueArr = $insertedArr;
                        }elseif ( $fixIdx==$rowIdx+1 ) {
                            $fakeValueArr = $insertedDetailArr;
                        }elseif ( $fixIdx==$rowIdx+2 ) {
                            $fixedTemplate[$fixIdx]  = $fixedTemplate[$fixIdx-1];
                            continue;
                        }else{
                            $currentColValue = $fixedTemplate[$fixIdx][$colIdx];
                            $fakeValueArr = array_map( fn($d) =>$currentColValue, $insertedArr);
                        }
                        array_splice( $fixedTemplate[$fixIdx], $colIdx, 1, $fakeValueArr );
                    }
                }
            }
        }
        return array_map(function($d){
            return implode("\t", $d);
        }, $fixedTemplate);
    }

    private function defineSpreadsheet() : void
    {
        $this->sp = new Spreadsheet();
        $this->sp->getProperties()
            ->setCreator( @$this->config['creator'] ?? 'HtmlPdfExcel' )
            ->setLastModifiedBy( @$this->config['last_modified_by'] ?? 'HtmlPdfExcel' )
            ->setTitle( @$this->config['title'] ?? uniqid() )
            ->setSubject( @$this->config['subject'] ?? uniqid() )
            ->setDescription(@$this->config['subject'] ?? "Report")
            ->setKeywords( @$this->config['keyword'] ?? "" )
            ->setCategory( @$this->config['category'] ?? "" );
    }

    private function getOrientation( $key ){
        if( !$key ){
            return pageSetup::ORIENTATION_DEFAULT;
        }
        if( strpos( strtolower($key), 'l' )!==false ){
            return pageSetup::ORIENTATION_LANDSCAPE;
        }elseif( strpos( strtolower($key), 'p' )!==false ){
            return pageSetup::ORIENTATION_PORTRAIT;
        }
        return pageSetup::ORIENTATION_DEFAULT;
    }

    public function render()
    {
        foreach( $this->data as $index => $dt ){
            if($this->break){
                $this->linesLength = 1;
            }
            
            $sheetTitle = @$dt['title'] ?? @$this->config['title'] ?? 'Sheet ';
            
            if( $index == 0 || !$this->break ){
                if(!$this->break && !isset($dt['title'])){
                    $sheetTitle .= ((string) (++$index));
                }
                $this->sp->getActiveSheet()->setTitle( $sheetTitle );
                //  page size and orientation
                $this->sp->getActiveSheet()->getPageSetup()->setOrientation($this->getOrientation(@$this->config['orientation']));
                $this->sp->getActiveSheet()->getPageSetup()->setPaperSize( @$this->config['size']);

                $this->generateSheet( $dt );
                $highestColumn = $this->sp->getActiveSheet()->getHighestColumn(); // e.g 'F'
                $highestColumnIndex = coor::columnIndexFromString($highestColumn);
                
                for($col = 1; $col <= $highestColumnIndex; ++$col) {
                    $this->sp
                    ->getActiveSheet()->getColumnDimension(coor::stringFromColumnIndex($col))
                    ->setAutoSize(true);            
                }
            }else{
                if( !isset($dt['title']) ){
                    $sheetTitle .= ((string) (++$index));
                }
                $workSheet = new Worksheet($this->sp, $sheetTitle);
                $this->sp->addSheet($workSheet, $index);
                $this->sp->setActiveSheetIndexByName($sheetTitle);
                
                //  page size and orientation
                $this->sp->getActiveSheet()->getPageSetup()->setOrientation($this->getOrientation(@$this->config['orientation']));
                $this->sp->getActiveSheet()->getPageSetup()->setPaperSize( @$this->config['size']);

                $this->generateSheet( $dt );
                for($col = 'A'; $col !== 'Z'; $col++) {
                    $this->sp->getActiveSheet()
                        ->getColumnDimension($col)
                        ->setAutoSize(true);        
                }
            }
        }
        $this->sp->setActiveSheetIndex(0);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.(@$this->config['title'] ?? date('Y-m-d_h-i-s ').uniqid()).'.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($this->sp, 'Xlsx');
        $writer->save('php://output');
    }

    private function generateRows( array $dataArray ) : array
    {
        $this->similiar = "";
        $currentTemplateArr = $this->isDynamic?$this->getDynamicTemplate( $dataArray ) : $this->templateArr;
        uksort($dataArray,function ($a,$b){
            return strlen($b)-strlen($a);
        });
        foreach($dataArray as $index => $rowData){
            if(is_array($rowData) && isset($rowData[0])){
                foreach(array_keys($rowData[0]) as $key){
                    $dataArray["sum.$index.$key"] = array_sum(array_column($rowData,$key));
                }
            }
        }
        foreach($currentTemplateArr AS $i => $dt){
            if( isset($currentTemplateArr[$i+1]) && $currentTemplateArr[$i+1]==$dt &&  $dt!==$this->similiar){
                $this->similiar  = $dt;
                foreach($dataArray as $dataIndex => $rowData){
                    if(is_array($rowData) && strpos($dt,'$'.$dataIndex.".")!==false ){
                        foreach( $rowData as $keyBaris=>$valueData ){
                            $originaldt = $dt;
                            uksort($valueData,function ($a,$b){
                                return strlen($b)-strlen($a);
                            });

                            foreach( $valueData as $keyCol=>$valueCol ){
                                if( is_array( $valueCol ) ){
                                    continue;
                                }
                                
                                $valueCol=$valueCol==""||$valueCol===null?" ":$valueCol;
                                $originaldt = str_replace('$'.$dataIndex.".".$keyCol, $valueCol, $originaldt);
                                $originaldt = str_replace("_number", $keyBaris+1, $originaldt);
                            }

                            foreach( $dataArray as $dataIndexHeader => $header ){
                                if( is_array( $header ) ){
                                    continue;
                                }
                                
                                $header=$header==""||$header===null?" ":$header;
                                $originaldt = str_replace('$'.$dataIndexHeader, $header, $originaldt);
                                $originaldt = $originaldt==""?"?":$originaldt;
                            }
                            
                            $perCols = explode( "\t",$originaldt );
                            $actualRows[] = $perCols; 
                            if( strpos( $originaldt, "!" )!==false ){
                                $indexSeru = [];
                                foreach($perCols as $idx => $colku){
                                    if(strpos($colku,'!')!==false){
                                        $indexSeru[]  = $idx;
                                    }
                                }
                                $indexDataConfig = count($actualRows);
                                if($indexDataConfig>1){
                                    $ketemu = true;
                                    for($iData = $indexDataConfig-2; $iData>=0;$iData--){
                                        $isSimiliar = false;
                                        foreach($indexSeru as $idx){
                                            if( $actualRows[$iData][$idx] =="?"){
                                                break;
                                            }else{
                                                if($actualRows[$iData] [$idx] == $actualRows[$indexDataConfig-1][$idx]){
                                                    $isSimiliar=true;
                                                }else{
                                                    $isSimiliar=false;
                                                    break 2;
                                                }
                                            }
                                        }
                                        if($isSimiliar){
                                            foreach($indexSeru as $idx){
                                                $actualRows[$indexDataConfig-1] [$idx]="?";
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }elseif($dt!==$this->similiar){
                $this->similiar  = $dt;
                    
                foreach($dataArray as $dataIndexHeader => $header){
                    if( is_array($header) ){
                        continue;
                    }
                    $header=$header==""||$header===null?" ":$header;
                    $dt = str_replace('$'.$dataIndexHeader, $header, $dt);
                }
                $perCols = explode( "\t", $dt );
                $actualRows[] = $perCols;
                $spacing=true;    
                foreach($perCols as $col){
                    if($col!=""){
                        $spacing=false;break;
                    }
                }        
            }
        }
        return $actualRows;
    }

    private function generateSheet( array $dataArray ) : void
    {
        $this->similiar = "";
        $parser = new StdMathParser();
        $evaluator = new Evaluator();
        $actualRows = $this->generateRows( $dataArray );
        $totalTable = [];

        //  formatting
        foreach($actualRows as $i => $baris){
            $mergeRowAda = null;
            $mergeRowKosong = null;
            foreach($baris as $j => $col){
                
                $koor = coor::stringFromColumnIndex($j+1).($i+1+$this->linesLength);
                if($col!="?"){
                    try{
                        if($col!==""){
                            $colConfig = "";
                            if(strpos($col,"::")!==false){
                                $colArray = explode("::", $col);
                                $col = $colArray[0];
                                $colConfig = strtolower($colArray[1]);
                            }
                            $fill = [];
                            if(strpos($colConfig,"g")!==false){
                                $fill = [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                                    'rotation' => 90,
                                    'startColor' => ['argb' => 'FFA0A0A0'],
                                    'endColor' => [ 'argb' => 'FFFFFFFF'],
                                ];
                            }
                            $alignment = Alignment::HORIZONTAL_LEFT;
                            if(strpos($colConfig,"c")!==false){
                                $alignment = Alignment::HORIZONTAL_CENTER;
                            }
                            if(strpos($colConfig,"r")!==false){
                                $alignment = Alignment::HORIZONTAL_RIGHT;
                            }
                            if(strpos($colConfig,"l")!==false){
                                $alignment = Alignment::HORIZONTAL_LEFT;
                            }
                            
                            if(strpos($colConfig,"y")!==false){
                                $fill = [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                                    'rotation' => 90,
                                    'startColor' => ['argb' => 'FF2EE74'],
                                    'endColor' => [ 'argb' => 'FFFFFFFF'],
                                ];
                            }
                            $rotation = 0;
                            $alignmentVertical = Alignment::VERTICAL_CENTER;
                            if(strpos($colConfig,"v")!==false){
                                $rotation = 90;
                                $this->sp->getActiveSheet()->getRowDimension($i+1+$this->linesLength)->setRowHeight(strlen($col)*6.5); 
                                $this->sp->getActiveSheet()->getStyle($koor)->getAlignment()->setWrapText(true);
                                // $alignmentVertical = Alignment::VERTICAL_BOTTOM;
                            }
                            $style = [
                                'font' => [
                                    'size'=> $this->fontSize,
                                    'bold' => strpos($colConfig,"b")!==false,
                                    'italic' => strpos($colConfig,"i")!==false,
                                    'underline' => strpos($colConfig,"u")!==false?'single':'none',
                                ],
                                'alignment' => [
                                    'horizontal' =>$alignment,                                
                                    'textRotation' => $rotation,
                                    'vertical' => $alignmentVertical,
                                ],
                                'borders' => strpos($colConfig,"t")!==false?[]:[
                                    'top' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    ],
                                    'right' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    ],
                                    'left' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    ],
                                    'bottom' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    ],
                                ],
                                'fill' => $fill,
                            ];
                            if(strpos($col,"<img")!==false){
                                
                                // $drawing->setPath('images/paid.png'); // put your path and image here
                                // $drawing->setCoordinates('B15');
                            }else{                            
                                if(strpos($col,"!")!==false){
                                    $col =str_replace("!","",$col);
                                }
                                if(strpos($colConfig,".")!==false){
                                    try{
                                        $col = $col==0?0.00:($parser->parse($col))->accept(new Evaluator());
                                    }catch(Exception $e){
                                        $col = $col;
                                    }
                                }else{
                                    $col = $col;
                                }
                                $this->sp->getActiveSheet()->setCellValueByColumnAndRow($j+1, $i+1+$this->linesLength, $col);
                                
                            }
                            
                            $this->sp->getActiveSheet()->getStyle($koor)->applyFromArray($style);
                            if(strpos($colConfig,".")!==false && is_numeric($col) ){
                                $this->sp->getActiveSheet()->getStyle($koor)->getNumberFormat()->setFormatCode('#,##0.00');
                            }
                            $mergeRowAda = $koor;
                            $koorSamping=null;
                            foreach($baris as $colindex => $mycol){
                                if($colindex<=$j){continue;}
                                if( $mycol==''  && ((isset($actualRows[$i-1][$colindex]) && $actualRows[$i-1][$colindex]=='') || !isset($actualRows[$i-1][$colindex]) )){
                                    $koorSamping = coor::stringFromColumnIndex($colindex+1).($i+1+$this->linesLength);
                                }else{
                                    break;
                                }
                            }
                            if($koorSamping!==null){
                                $this->sp->getActiveSheet()->mergeCells("$koor:$koorSamping");
                                $this->sp->getActiveSheet()->getStyle("$koor:$koorSamping")->applyFromArray($style);
                            }
                            if( strpos( $colConfig, "h" )!==false ){
                                $koorBawah=null;
                                foreach($actualRows as $myindex => $mybaris){
                                    if($myindex<=$i){continue;}
                                    if( $mybaris[$j]=="" && $myindex!=count($actualRows) ){
                                        $koorBawah = coor::stringFromColumnIndex($j+1).($myindex+1+$this->linesLength);
                                    }else{
                                        break;
                                    }
                                }
                                
                                if($koorBawah!==null){
                                    $this->sp->getActiveSheet()->mergeCells("$koor:$koorBawah");
                                    $this->sp->getActiveSheet()->getStyle("$koor:$koorBawah")->applyFromArray($style);
                                }
                            }
                            
                        }  
                    }catch(Exception $e){
                        trigger_error( $e->getFile()." ".$e->getMessage()." ".$e->getLine() );
                    }
                }
            }
        }
        $this->linesLength += count($actualRows)+1;
    }
}

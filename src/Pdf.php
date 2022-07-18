<?php

namespace Starlight93\HtmlPdfExcel;

class Pdf extends Html {
    public function render()
    {
        
        $htmlArray = [];
        foreach( $this->data as $index => $dt ){
            $htmlArray[] = $this->generateHtml( $dt );
        }
        
        $pageOrientation = @$this->config['orientation'] ?? "L";
        $pageSize  = @$this->config['size'] ?? "F4";
        $pageTitle = @$this->config['title'] ?? uniqid();

        $pdf = new CustomPDF();

        $pdf->setHeaderData( array_merge( @$this->config['header'] ?? [],[
            'header_callback'=>@$this->config['header_callback'],
            'footer_callback'=>@$this->config['footer_callback']
        ]) );
        
        $pdf->SetMargins(@$this->config['left']??8, @$this->config['top']??8, @$this->config['right']??8, keepmargins:false );
        if( !@$this->config['header'] && !@$this->config['header_callback'] ){
            $pdf->SetHeaderMargin(5);
            $pdf->SetPrintHeader( false );
        }else{
            $pdf->SetHeaderMargin(5);
            $pdf->SetPrintHeader( true );
        }

        if( !@$this->config['footer'] && !@$this->config['footer_callback'] ){
            $pdf->SetPrintFooter( false );
        }else{
            $pdf->SetPrintFooter( true );
        }

        $pdf->PageNo();
        $pdf->setTitle( $pageTitle );

        if( @$this->config['callback'] ){
            $function = $this->config['callback'];
            $function( $pdf );
        }

        if($this->break){
            foreach($htmlArray as $html){
                $pdf->AddPage($pageOrientation, $pageSize);
                if( @$this->config['background'] ){
                    $bg = $this->config['background'];
                    // get the current page break margin
                    $bMargin = $pdf->getBreakMargin();
                    // get current auto-page-break mode
                    $auto_page_break = $pdf->getAutoPageBreak();
                    $pdf->SetAutoPageBreak(false, 0);
                    
                    $imgPath = $bg['path'];
                    $x = @$bg['x'] ?? '';
                    $y = @$bg['y'] ?? '';
                    $width = @$bg['width'] ?? '';
                    $height = @$bg['height'] ?? '';
                    $type = @$bg['type'] ?? 'JPG';
                    $align = @$bg['align'] ?? 'L';
                    $pdf->Image($imgPath, $x, $y, $width, $height, $type, '', 'T', false, 300, $align, false, false, 0, false, false, false);
                    // restore auto-page-break status
                    $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
                    // set the starting point for the page content
                    $pdf->setPageMark();
                }

                $pdf->writeHTML( str_replace('1px', '0.5px', $html), true, false,true,false,'' );
            }
        }else{
            $pdf->AddPage($pageOrientation, $pageSize);
            $pdf->writeHTML( str_replace('1px', '0.5px', implode('', $htmlArray) ), true, false,true,false,'');
        }
        $pdf->Output($pageTitle.'.pdf','I');
    }
}
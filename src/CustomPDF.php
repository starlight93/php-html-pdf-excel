<?php

namespace Starlight93\HtmlPdfExcel;
use TCPDF;

class CustomPDF extends TCPDF {
    
    public function Header(){
        $params = $this->getHeaderData()['logo'];

        if( @$params['header_callback'] ){
            $function = $params['header_callback'];
            $function( $this );
        }

        $this->SetFont('helvetica', 'N', 8);

        if( @$params['title'] ){
            $text = @$params['title']['text'] ?? '';
            $align = @$params['title']['align'] ?? 'R';
            $this->Cell( 0, 15, $text, 0, false, $align, 0, '', 0, false, 'M', 'M');
        }

        if( @$params['html'] ){
            // $this->writeHTML( $params['html'] );
        }
        
        if( @$params['image'] ){
            $imgPath = @$params['image']['path'];
            $x = @$params['image']['x'] ?? '';
            $y = @$params['image']['y'] ?? '';
            $width = @$params['image']['width'] ?? '';
            $height = @$params['image']['height'] ?? '';
            $type = @$params['image']['type'] ?? 'JPG';
            $align = @$params['image']['align'] ?? 'L';
            $this->Image($imgPath, $x, $y, $width, $height, $type, '', 'T', false, 300, $align, false, false, 0, false, false, false);    
        }
    }

    public function Footer() {
        $params = $this->getHeaderData()['logo'];
        $this->SetY(-10);
        $this->SetLeftMargin(18);
        $this->SetFont('helvetica', 'I', 8);
        if( @$params['footer_callback'] ){
            $function = $params['footer_callback'];
            $function( $this );
        }
    }
}
<?php

namespace Starlight93\HtmlPdfExcel;

class Renderer {
    protected $isTesting = false;

    function __construct( bool $isTest=false ){
        $this->isTesting = $isTest;
    }

    public function getFromFile( $path ){
        $file = fopen( $path, "r" );
        $dt =  fread( $file ,filesize($path) ) ;
        fclose($file);
        return $dt;
    }

    public function renderXls( array $data, string $template, array $config )
    {
        if($this->isTesting){
            $data = json_decode( $this->getFromFile( __DIR__."/../testing/1data.json" ), true);
            $template = $this->getFromFile( __DIR__."/../testing/1template.txt" );
        }
        $renderer = new Excel( $data, $template, $config );
        return $renderer->render();
    }


    public function renderHtml( array $data, string $template, array $config )
    {
        if($this->isTesting){
            $data = json_decode( $this->getFromFile( __DIR__."/../testing/1data.json" ), true);
            $template = $this->getFromFile( __DIR__."/../testing/1template.txt" );
        }
        $renderer = new Html( $data, $template, $config );
        return $renderer->render();
    }


    public function renderPDF( array $data, string $template, array $config )
    {
        if($this->isTesting){
            $data = json_decode( $this->getFromFile( __DIR__."/../testing/1data.json" ), true);
            $template = $this->getFromFile( __DIR__."/../testing/1template.txt" );
        }
        $renderer = new Pdf( $data, $template, $config );
        return $renderer->render();
    }

}